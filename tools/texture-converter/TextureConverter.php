<?php
/**
 * Convert source textures data to sprites.
 * Generate sprite files and return summary data for int in JSON format.
 * Example:
 * ```
 * php ./TextureConverter.php <directoryWithSourceFiles> <directoryWithResults>
 * ```
 */

$sourceDirectory = $argv[1];
$resultDirectory = $argv[2];
if (!is_dir($sourceDirectory)) {
    echo "Source directory '{$sourceDirectory} does not exist.\n";
    return 1;
}
$result = (new TextureConverter())->processDirectory($sourceDirectory, $resultDirectory);
echo json_encode($result);
return 0;


class TextureConverter
{
    public $shadowDirName = 'Shadow';
    public $skippedColors = [
        [0, 255, 255],
    ];
    public $replacedColors = [
        [[255, 0, 255], [0, 0, 0, 75]],
        [[255, 150, 255], [100, 100, 100, 75]],
    ];

    /**
     * @param string $sourceDir
     * @param string $resultDir
     * @return array
     */
    public function processDirectory($sourceDir, $resultDir)
    {
        if ($this->isDirEssenceRoot($sourceDir)) {
            if (!file_exists(dirname($resultDir))) {
                mkdir(dirname($resultDir), null, true);
            }
            $resultFile = $resultDir . '.png';
            return $this->processEssence($sourceDir, $resultFile);
        }
        $data = [];
        foreach (scandir($sourceDir) as $subdir) {
            if (in_array($subdir, ['.', '..'])) {
                continue;
            }
            $data[$subdir] = $this->processDirectory("{$sourceDir}/{$subdir}", "{$resultDir}/{$subdir}");
        }
        return $data;
    }

    /**
     * @param string $directory
     * @return bool True if specified directory contains essence files.
     */
    private function isDirEssenceRoot($directory)
    {
        return count(preg_grep('/.+\.hdl$/i', scandir($directory))) > 0;
    }

    /**
     * @param string $rootDir
     * @param string $resultFilePath
     * @return array [
     *   'width' => int $frameWidth,
     *   'height' => int $frameHeight,
     *   'groups' => [
     *     int $groupNumber => int[] $frameOffsets
     *   ]
     * ]
     */
    private function processEssence($rootDir, $resultFilePath)
    {
        $hdlFileName = reset(preg_grep('/.+\.hdl$/i', scandir($rootDir)));
        $scheme = $this->parseHdlFile("{$rootDir}/{$hdlFileName}");
        $spriteData = $this->generateSprite($rootDir, $scheme, $resultFilePath);
        return $this->generateSpriteData($scheme, $spriteData);
    }

    /**
     * @param string $filePath
     * @return array [
     *   int $groupNumber => [
     *     ['file' => string $fileName, 'framesCount' => int $framesCount]
     *   ]
     * ]
     */
    private function parseHdlFile($filePath)
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $result = [];
        foreach ($lines as $line) {
            if (!preg_match('/^Group(\d+)=/', $line, $matches)) {
                continue;
            }
            $groupNumber = $matches[1];
            $files = array_filter(explode('|', substr($line, strpos($line, '=') + 1)));
            $frames = [];
            $prevFile = null;
            foreach ($files as $file) {
                if ($file == $prevFile) {
                    $frames[count($frames) - 1]['framesCount']++;
                    continue;
                }
                $frames[] = ['file' => $file, 'framesCount' => 1];
                $prevFile = $file;
            }
            $result[$groupNumber] = $frames;
        }
        return $result;
    }

    /**
     * @param string $rootDir
     * @param array $scheme
     * @param string $resultFilePath
     * @return array [
     *   'frameWidth' => int $frameWidth,
     *   'frameHeight' => int $frameHeight
     *   'offsets' => array [string $imageName => int $verticalOffset],
     * ]
     */
    private function generateSprite($rootDir, $scheme, $resultFilePath)
    {
        $imageResources = [];
        foreach ($scheme as $groupNumber => $images) {
            foreach ($images as $imageData) {
                $imageResources[$imageData['file']] = $this->processImage(
                    "{$rootDir}/{$imageData['file']}",
                    "{$rootDir}/{$this->shadowDirName}/{$imageData['file']}"
                );
            }
        }
        $spriteData = $this->unionImages($imageResources);
        imagepng($spriteData['image'], $resultFilePath);
        unset($spriteData['image']);
        return $spriteData;
    }

    /**
     * @param string $sourceFilePath
     * @param string $shadowFilePath
     * @return resource
     */
    private function processImage($sourceFilePath, $shadowFilePath)
    {
        list($width, $height) = getimagesize($sourceFilePath);
        $sourceImage = imagecreatefrombmp($sourceFilePath);
        $shadowImage = imagecreatefrombmp($shadowFilePath);
        $newImage = imagecreate($width, $height);
        imagecolorallocatealpha($newImage, 0, 0, 0, 127);

        $newImage = $this->copyImage($sourceImage, $newImage, $width, $height);
        $newImage = $this->copyShadow($shadowImage, $newImage, $width, $height);
        return $newImage;
    }

    /**
     * @param resource $sourceImage
     * @param resource $newImage
     * @param int $width
     * @param int $height
     * @return resource
     */
    private function copyImage($sourceImage, $newImage, $width, $height)
    {
        $skippedColors = [];
        foreach ($this->skippedColors as $sourceColor) {
            $skippedColors[] = imagecolorresolve($sourceImage, ...$sourceColor);
        }
        $colorIndexes = [];
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $sourceColorIndex = imagecolorat($sourceImage, $x, $y);
                if (in_array($sourceColorIndex, $skippedColors)) {
                    continue;
                }
                if (isset($colorIndexes[$sourceColorIndex])) {
                    $newColorIndex = $colorIndexes[$sourceColorIndex];
                } else {
                    $rgb = imagecolorsforindex($sourceImage, $sourceColorIndex);
                    $newColorIndex = imagecolorallocate($newImage, $rgb['red'], $rgb['green'], $rgb['blue']);
                    $colorIndexes[$sourceColorIndex] = $newColorIndex;
                }
                imagesetpixel($newImage, $x, $y, $newColorIndex);
            }
        }
        return $newImage;
    }

    /**
     * @param resource $shadowImage
     * @param resource $newImage
     * @param int $width
     * @param int $height
     * @return resource
     */
    private function copyShadow($shadowImage, $newImage, $width, $height)
    {
        $replacedColors = [];
        foreach ($this->replacedColors as list($sourceColor, $replaceColor)) {
            $sourceIndex = imagecolorresolve($shadowImage, ...$sourceColor);
            $replaceIndex = imagecolorallocatealpha($newImage, ...$replaceColor);
            $replacedColors[$sourceIndex] = $replaceIndex;
        }
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $sourceColorIndex = imagecolorat($shadowImage, $x, $y);
                if (!isset($replacedColors[$sourceColorIndex])) {
                    continue;
                }
                $newColorIndex = $replacedColors[$sourceColorIndex];
                imagesetpixel($newImage, $x, $y, $newColorIndex);
            }
        }
        return $newImage;
    }

    /**
     * @param array $images [string $imageName => resources $fileResource]
     * @return array [
     *   'frameWidth' => int $frameWidth,
     *   'frameHeight' => int $frameHeight
     *   'offsets' => array [string $imageName => int $verticalOffset],
     *   'image' => resource $image
     * ]
     */
    private function unionImages($images)
    {
        $firstImage = reset($images);
        $frameWidth = imagesx($firstImage);
        $frameHeight = imagesy($firstImage);
        $result = [
            'frameWidth' => $frameWidth,
            'frameHeight' => $frameHeight,
            'offsets' => [],
        ];
        $totalHeight = count($images) * $frameHeight;
        $resultImage = imagecreate($frameWidth, $totalHeight);
        imagecolorallocatealpha($resultImage, 0, 0, 0, 127);

        $i = 0;
        foreach ($images as $name => $image) {
            $offset = $i * $frameHeight;
            imagecopy($resultImage, $image, 0, $offset, 0, 0, $frameWidth, $frameHeight);
            $result['offsets'][$name] = $offset;
            $i++;
        }
        $result['image'] = $resultImage;
        return $result;
    }

    /**
     * @param array $scheme
     * @param array $imageData
     * @return array [
     *   'width' => int $frameWidth,
     *   'height' => int $frameHeight,
     *   'groups' => [
     *     int $groupNumber => int[] $frameOffsets
     *   ]
     * ]
     */
    private function generateSpriteData($scheme, $imageData)
    {
        $result = [
            'width' => $imageData['frameWidth'],
            'height' => $imageData['frameHeight'],
            'groups' => [],
        ];
        foreach ($scheme as $groupNumber => $groupData) {
            $result['groups'][$groupNumber] = [];
            foreach ($groupData as $frameData) {
                $offset = $imageData['offsets'][$frameData['file']];
                for ($i = 0; $i < $frameData['framesCount']; $i++) {
                    $result['groups'][$groupNumber][] = $offset;
                }
            }
        }
        return $result;
    }
}