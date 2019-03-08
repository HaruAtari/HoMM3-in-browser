<?php

$sourceDir = __DIR__ . '/test';
$resultDir = __DIR__ . '/../..';

$data = (new Converter())->convert($sourceDir, $resultDir);
echo json_encode($data);

class Converter
{
    const REPLACED_COLORS = [
        [[255, 0, 255], [0, 0, 0, 75]],
        [[255, 150, 255], [100, 100, 100, 75]],
    ];
    const SKIPPED_COLORS = [
        [0, 255, 255],
    ];

    /**
     * @param string $rawDir
     * @param string $resultDir
     * @return array [
     *     string $spriteName => [
     *     'width' => int $frameWidth,
     *     'height' => int $frameHeight,
     *     'groups'=> [
     *       string $groupName => int[] $frameOffsets
     *     ]
     *   ]
     * ]
     */
    public function convert($rawDir, $resultDir)
    {
        $result = [];
        foreach (scandir($rawDir) as $spriteName) {
            if (in_array($spriteName, ['.', '..'])) {
                continue;
            }
            $spriteDir = "{$rawDir}/{$spriteName}";
            $resultFilePath = "{$resultDir}/{$spriteName}.png";
            $result[$spriteName] = $this->processDir($spriteDir, $resultFilePath);
        }
        return $result;
    }

    /**
     * @param string $rawDir
     * @param string $resultFilePath
     * @return array [
     *   'width' => int $frameWidth,
     *   'height' => int $frameHeight,
     *   'groups'=> [
     *     string $groupName => int[] $frameOffsets
     *   ]
     * ]
     */
    private function processDir($rawDir, $resultFilePath)
    {
        $parts = [];
        foreach (scandir($rawDir) as $group) {
            if ($group == '.' || $group == '..') {
                continue;
            }
            $parts[$group] = [];
            foreach (scandir("{$rawDir}/{$group}") as $file) {
                if (in_array($file, ['.', '..'])) {
                    continue;
                }
                $filePath = "{$rawDir}/{$group}/{$file}";
                $convertedImage = $this->convertImage($filePath);
                $parts[$group][] = $convertedImage;
            }
        }
        return $this->unionImages($parts, $resultFilePath);
    }

    /**
     * @param string $rawImgPath
     * @return resource Processed image with transparent background and shadow.
     */
    private function convertImage($rawImgPath)
    {
        list($width, $height) = getimagesize($rawImgPath);
        $rawImg = imagecreatefrombmp($rawImgPath);
        $newImg = imagecreate($width, $height);
        imagecolorallocatealpha($newImg, 0, 0, 0, 127);

        $replacedColors = [];
        foreach (static::REPLACED_COLORS as list($sourceColor, $replaceColor)) {
            $sourceIndex = imagecolorresolve($rawImg, ...$sourceColor);
            $replaceIndex = imagecolorallocatealpha($newImg, ...$replaceColor);
            $replacedColors[$sourceIndex] = $replaceIndex;
        }
        $skippedColors = [];
        foreach (static::SKIPPED_COLORS as $sourceColor) {
            $skippedColors[] = imagecolorresolve($rawImg, ...$sourceColor);
        }
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rawColorIndex = imagecolorat($rawImg, $x, $y);
                if (in_array($rawColorIndex, $skippedColors)) {
                    continue;
                }
                if (isset($replacedColors[$rawColorIndex])) {
                    $newColorIndex = $replacedColors[$rawColorIndex];
                } else {
                    $rgb = imagecolorsforindex($rawImg, $rawColorIndex);
                    $newColorIndex = imagecolorallocate($newImg, $rgb['red'], $rgb['green'], $rgb['blue']);
                    $replacedColors[$rawColorIndex] = $newColorIndex;
                }
                imagesetpixel($newImg, $x, $y, $newColorIndex);
            }
        }
        return $newImg;
    }

    /**
     * @param array $parts [string $group => resource[] $images]
     * @param string $resultPalePath
     * @return array [
     *   'width' => int $frameWidth,
     *   'height' => int $frameHeight,
     *   'groups'=> [
     *     string $groupName => int[] $frameOffsets
     *   ]
     * ]
     */
    private function unionImages($parts, $resultPalePath)
    {
        $firstImage = reset($parts)[0];
        $imageWidth = imagesx($firstImage);
        $imageHeight = imagesy($firstImage);
        $data = [
            'width' => $imageWidth,
            'height' => $imageHeight,
            'groups' => [],
        ];

        $imagesCount = 0;
        foreach ($parts as $groupName => $files) {
            $imagesCount += count($files);
            $data['groups'][$groupName] = [];
        }

        $totalHeight = $imageHeight * $imagesCount;
        $resultImage = imagecreate($imageWidth, $totalHeight);
        imagecolorallocatealpha($resultImage, 0, 0, 0, 127);

        $i = 0;
        foreach ($parts as $groupName => $groupFiles) {
            foreach ($groupFiles as $img) {
                $offset = $i * $imageHeight;
                imagecopy($resultImage, $img, 0, $offset, 0, 0, $imageWidth, $imageHeight);
                $data['groups'][$groupName][] = $offset;
                $i++;
            }
        }
        imagepng($resultImage, $resultPalePath);
        return $data;
    }
}