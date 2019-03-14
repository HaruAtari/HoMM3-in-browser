<?php
/**
 * Convert source textures data to sprites.
 * Generate sprite files and return summary data for int in JSON format.
 * Example:
 * ```
 * php ./TextureConverter.php <directoryWithSourceFiles> <directoryWithResults> [<outputDataFile>]
 * ```
 */
if (count($argv) != 4) {
    echo "Incorrect arguments! Command has a next signature:\n";
    echo "\n";
    echo "  php ./converter.php <directoryWithSourceFiles> <directoryWithResults> <outputDataFile>\n";
    echo "\n";
    echo "  * <directoryWithSourceFiles> - Path to directory with source images and .hdl files.\n";
    echo "  * <directoryWithResults> - Path to directory for result images.\n";
    echo "  * <outputDataFile> - The .json file for result sprites metadata.\n";
    return 1;
}

$sourceDirectory = realpath($argv[1]);
$resultDirectory = realpath($argv[2]);

if (!is_dir($sourceDirectory)) {
    echo "Source directory '{$sourceDirectory} does not exist.\n";
    return 1;
}
if (!is_dir($resultDirectory)) {
    echo "Result directory '{$resultDirectory} does not exist.\n";
    return 1;
}
$outputFile=$argv[3];

$logger = new Logger();
$result = (new TextureConverter($logger))->processDirectoriesTree($sourceDirectory, $resultDirectory);
if ($outputFile) {
    file_put_contents($outputFile, json_encode($result));
} else {
    echo "\n";
    echo json_encode($result);
}
return 0;

class TextureConverter
{
    const TYPE_SPELL = 0;
    const TYPE_CREATURE = 2;
    const TYPE_MAP_OBJECT = 3;
    const TYPE_HERO = 4;
    const TYPE_TERRAIN = 5;
    const TYPE_CURSOR = 6;
    const TYPE_INTERFACE = 7;
    const TYPE_COMBAT_HERO = 9;

    const SHADOW_TRANSPARENT = 75;
    public $shadowDirName = 'Shadow';
    public $skippedColors = [
        [0, 255, 255],
        [0, 254, 255],
        [255, 50, 255],
        [255, 100, 255],
    ];
    public $replacedColors = [
        [[255, 0, 255], [0, 0, 0, self::SHADOW_TRANSPARENT]],
        [[255, 50, 255], [50, 50, 50, self::SHADOW_TRANSPARENT]],
        [[255, 100, 255], [100, 100, 100, self::SHADOW_TRANSPARENT]],
        [[255, 150, 255], [150, 150, 150, self::SHADOW_TRANSPARENT]],
    ];
    public $typeNames = [
        self::TYPE_SPELL => 'spell',
        self::TYPE_CREATURE => 'creature',
        self::TYPE_MAP_OBJECT => 'map-object',
        self::TYPE_HERO => 'hero',
        self::TYPE_TERRAIN => 'terrain',
        self::TYPE_CURSOR => 'cursor',
        self::TYPE_INTERFACE => 'interface',
        self::TYPE_COMBAT_HERO => 'combat-hero',
    ];
    public $groupNames = [
        self::TYPE_SPELL => ['default'],
        self::TYPE_CREATURE => [
            '0' => 'moving',
            '1' => 'mouse-over',
            '2' => 'standing',
            '3' => 'getting-hit',
            '4' => 'defend',
            '5' => 'death',
            '7' => 'turn-left',
            '8' => 'turn-right',
            '9' => 'turn-left',
            '10' => 'turn-right',
            '11' => 'attack-up',
            '12' => 'attack-straight',
            '13' => 'attack-down',
            '20' => 'start-moving',
            '21' => 'stop-moving',
        ],
        self::TYPE_MAP_OBJECT => ['default'],
        self::TYPE_HERO => [
            'up',
            'up-right',
            'right',
            'down-right',
            'down',
            'move-up',
            'move-up-right',
            'move-right',
            'move-down-right',
            'move-down',
        ],
        self::TYPE_TERRAIN => ['default'],
        self::TYPE_CURSOR => ['default'],
        self::TYPE_INTERFACE => ['default'],
        self::TYPE_COMBAT_HERO => [
            'standing',
            'shuffle',
            'failure',
            'victory',
            'cast-spell',
        ],
    ];
    /** @var Logger */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $sourceRootDir
     * @param string $resultDir
     * @return array
     */
    public function processDirectoriesTree($sourceRootDir, $resultDir)
    {
        $result = [];
        foreach (scandir($sourceRootDir) as $subdir) {
            if (in_array($subdir, ['.', '..'])) {
                continue;
            }
            if(!is_dir("{$sourceRootDir}/{$subdir}")){
                continue;
            }
            $this->logger->log("Parse {$subdir} directory");
            $dirResult = $this->processDirectory("{$sourceRootDir}/{$subdir}", $resultDir);
            $result = array_merge($result, $dirResult);
        }
        $this->logger->log("Result files are placing in {$resultDir}");
        return $result;
    }

    /**
     * @param string $sourceDir
     * @param string $resultDir
     * @return array
     */
    private function processDirectory($sourceDir, $resultDir)
    {
        $result = [];
        $files = $this->getHdlFiles($sourceDir);
        $filesCount = count($files);
        $this->logger->log("Files for processing: {$filesCount}");
        foreach ($files as $i => $fileName) {
            $i++;
            $sourceFile = "{$sourceDir}/{$fileName}";
            $essenceName = str_replace('.hdl', '', $fileName);
            $resultFileName = "{$essenceName}.png";
            $essenceData = $this->processEssence($sourceFile, $resultDir, $resultFileName);
            $result[$essenceName] = $essenceData;
            $this->logger->log("[{$i} of {$filesCount}] {$essenceName} was processed", 1);
        }
        return $result;
    }

    /**
     * @param string $directory
     * @return string[] File names
     */
    private function getHdlFiles($directory)
    {
        $result = [];
        foreach (scandir($directory) as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }
            if (strpos($file, '.hdl') !== false) {
                $result[] = $file;
            }
        }
        return $result;
    }

    /**
     * @param string $hdlFilePath
     * @param string $resultDir
     * @param string $resultFileName
     * @return array [
     *   'width' => int $frameWidth,
     *   'height' => int $frameHeight,
     *   'path' => string $relativeFilePath
     *   'groups' => [
     *     int[] $frameOffsets
     *   ]
     * ]
     */
    private function processEssence($hdlFilePath, $resultDir, $resultFileName)
    {
        $scheme = $this->parseHdlFile($hdlFilePath);
        $resultFilePath = "{$resultDir}/{$scheme['type']}/{$resultFileName}";
        if (!file_exists(dirname($resultFilePath))) {
            mkdir(dirname($resultFilePath), null, true);
        }
        $spriteData = $this->generateSprite(dirname($hdlFilePath), $scheme, $resultFilePath);
        $result = $this->generateSpriteData($scheme, $spriteData);
        $result['path'] = "{$scheme['type']}/{$resultFileName}";
        return $result;
    }

    /**
     * @param string $filePath
     * @return array [
     *   'type' => string $typeName,
     *   'groups' => [
     *     string $groupName => [
     *       ['file' => string $fileName, 'framesCount' => int $framesCount]
     *     ],
     *   ],
     * ]
     */
    private function parseHdlFile($filePath)
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $result = ['groups' => []];
        $type = null;
        foreach ($lines as $line) {
            if (preg_match('/^Type=(\d+)/', $line, $matches)) {
                $type = $matches[1];
                continue;
            }
            if (preg_match('/^Group(\d+)=/', $line, $matches)) {
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
                $groupNumber = $matches[1];
                $groupName = $this->groupNames[$type][$groupNumber];
                $result['groups'][$groupName] = $frames;
            }
        }
        $result['type'] = $this->typeNames[$type];
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
        foreach ($scheme['groups'] as $groupNumber => $images) {
            foreach ($images as $imageData) {
                $sourceFilePath = "{$rootDir}/{$imageData['file']}";
                $shadowFilePath = "{$rootDir}/{$this->shadowDirName}/{$imageData['file']}";
                $imageResources[$imageData['file']] = $this->processImage($sourceFilePath, $shadowFilePath);
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
    private function processImage($sourceFilePath, $shadowFilePath = null)
    {
        list($width, $height) = getimagesize($sourceFilePath);
        $sourceImage = imagecreatefrombmp($sourceFilePath);
        $newImage = imagecreate($width, $height);
        imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        $newImage = $this->copyImage($sourceImage, $newImage, $width, $height);
        $newImage = $this->copyShadow($sourceImage, $newImage, $width, $height);
        if ($shadowFilePath && file_exists($shadowFilePath)) {
            $shadowImage = imagecreatefrombmp($shadowFilePath);
            $newImage = $this->copyShadow($shadowImage, $newImage, $width, $height);
        }
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
     *   'offsets' => array [string $imageName => int[] $offset],
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
        $totalWidth = $frameWidth;
        $totalHeight = count($images) * $frameHeight;
        $resultImage = imagecreate($totalWidth, $totalHeight);
        imagecolorallocatealpha($resultImage, 0, 0, 0, 127);

        $i = 0;
        foreach ($images as $name => $image) {
            $offsetX = 0;
            $offsetY = $i * $frameHeight;
            imagecopy($resultImage, $image, $offsetX, $offsetY, 0, 0, $frameWidth, $frameHeight);
            $result['offsets'][$name] = [$offsetX, $offsetY];
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
        foreach ($scheme['groups'] as $groupName => $groupData) {
            $result['groups'][$groupName] = [];
            foreach ($groupData as $frameData) {
                $offset = $imageData['offsets'][$frameData['file']];
                for ($i = 0; $i < $frameData['framesCount']; $i++) {
                    $result['groups'][$groupName][] = $offset;
                }
            }
        }
        return $result;
    }
}

class Logger
{
    /**
     * @param string $message
     * @param int $indent
     */
    public function log($message, $indent = 0)
    {
        echo str_repeat('  ', $indent);
        echo $message;
        echo "\n";
    }
}