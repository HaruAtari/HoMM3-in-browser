<?php

$config = json_decode(file_get_contents(__DIR__ . '/original-resources-extractor.config.json'), true);
$textureGenerator = new TextureGenerator();
$extractor = new Extractor($config, $textureGenerator);
$extractor->extract();

class Extractor
{
    private $pythonCommand;
    private $sourceDir;
    private $tempDir;
    private $resultDir;
    private $onlyLodFiles = null;
    private $spriteDataFilename;
    /** @var TextureGenerator */
    private $textureGenerator;

    /**
     * @param array $config
     * @param TextureGenerator $textureGenerator
     */
    public function __construct(array $config, $textureGenerator)
    {
        foreach ($config as $field => $value) {
            $this->{$field} = $value;
        }
        $this->textureGenerator = $textureGenerator;
    }

    public function extract()
    {
        if (!$this->checkUserConfirm()) {
            return false;
        }
        echo "Step 1: Prepare environment\n";
        $this->prepareEnvironment();
        echo "Step 2: Extract .LOD files\n";
        $lodDirs = $this->extractLodFiles($this->sourceDir, $this->tempDir);
        echo "Step 3: Extract .DEF files\n";
        $defDirs = $this->extractDefFiles($lodDirs, $this->tempDir);
        echo "Step 4: Generate sprites\n";
        $this->generateSprites($defDirs, $this->resultDir);
        echo "Step 5: Remove temporary data\n";
        $this->removeTempData();

    }

    /**
     * @return bool
     */
    private function checkUserConfirm()
    {
        echo 'Script will run with next arguments:' . "\n" .
            '  Source directory: ' . $this->sourceDir . "\n" .
            '  Temp directory: ' . $this->tempDir . "\n" .
            '  Result directory: ' . $this->resultDir . "\n";
        $input = readline('Are you agree? [Print Y or N]: ');
        return strtolower(trim($input)) == 'y';
    }

    private function prepareEnvironment()
    {
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, null, true);
        }
        if (!file_exists($this->resultDir)) {
            mkdir($this->resultDir, null, true);
        }
    }

    /**
     * @param string $sourceDir
     * @param string $resultDir
     * @return string[] result directories list
     */
    private function extractLodFiles($sourceDir, $resultDir)
    {
        $result = [];
        foreach (scandir($sourceDir) as $file) {
            if (strpos(strtolower($file), '.lod') === false) {
                continue;
            }
            if ($this->onlyLodFiles && !in_array($file, $this->onlyLodFiles)) {
                echo "  [skipped] {$file}\n";
                continue;
            }
            echo "  {$file}\n";
            $sourceFilePath = "{$sourceDir}/{$file}";
            $resultDirPath = "{$resultDir}/lod/{$file}";
            mkdir($resultDirPath, null, true);
            list($exitCode) = $this->execPython("lodextract.py {$sourceFilePath} {$resultDirPath}");
            if ($exitCode == 0) {
                $result[] = $resultDirPath;
            }
        }
        return $result;
    }

    /**
     * @param string[] $lodDirs
     * @param string $resultDir
     * @return string[] result directories list
     */
    private function extractDefFiles($lodDirs, $resultDir)
    {
        $result = [];
        foreach ($lodDirs as $rootDir) {
            $lodName = basename($rootDir);
            echo "  {$rootDir}";
            $totalFilesCount = 0;
            $successFilesCount = 0;
            foreach (scandir($rootDir) as $file) {
                if (strpos(strtolower($file), '.def') === false) {
                    continue;
                }
                $totalFilesCount++;
                $sourceFilePath = "{$rootDir}/{$file}";
                $resultDirPath = "{$resultDir}/def/{$lodName}/{$file}";
                mkdir($resultDirPath, null, true);
                list($exitCode) = $this->execPython("defextract.py {$sourceFilePath} {$resultDirPath}");
                if ($exitCode == 0) {
                    $result[] = $resultDirPath;
                    $successFilesCount++;
                }
            }
            echo " {$successFilesCount} of {$totalFilesCount}\n";
        }
        return $result;
    }

    /**
     * @param string[] $defDirs
     * @param string $resultDir
     * @return array [string $spriteName => array $spriteData]
     *   SpriteData:
     *   array [
     *     'file' => string $refFilePath,
     *     'groups' => [
     *       int $groupId => [
     *         [int $offsetX, int $offsetY]
     *       ]
     *     ]
     *   ]
     */
    private function generateSprites($defDirs, $resultDir)
    {
        $resultDir = "{$resultDir}/sprites";
        $result = [];
        foreach ($defDirs as $dir) {
            echo "  {$dir}\n";
            list($name, $data) = $this->textureGenerator->generate($dir, $resultDir);
            $result[$name] = $data;
        }
        file_put_contents("{$resultDir}/{$this->spriteDataFilename}", json_encode($result));
        return $result;
    }

    private function removeTempData()
    {
        $this->deleteDirectory($this->tempDir);
    }

    /**
     * @param string $command
     * @return array [string $exitCode, string $output]
     */
    private function execPython($command)
    {
        exec($this->pythonCommand . ' ' . $command, $output, $exitCode);
        return [$exitCode, $output];
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory("{$dir}/{$item}")) {
                return false;
            }
        }
        return rmdir($dir);
    }
}

class TextureGenerator
{
    /**
     * @param string $sourceDir
     * @param string $resultDir
     *
     * @return array [string $spriteName, array $spriteData]
     *   SpriteData:
     *   array [
     *     'file' => string $refFilePath,
     *     'groups' => [
     *       int $groupId => [
     *         [int $offsetX, int $offsetY]
     *       ]
     *     ]
     *   ]
     */
    public function generate($sourceDir, $resultDir)
    {
        $schemeFilePath = $this->getSchemeFilePath($sourceDir);
        $resourcesDir = $this->getResourcesDir($sourceDir);
        $scheme = $this->loadScheme($schemeFilePath);
        list($absResultFilePath, $relResultFilePath) = $this->getResultFilePath($scheme, $sourceDir, $resultDir);
        $data = $this->generateSprite($scheme, $resourcesDir, $absResultFilePath);
        $spriteData = [
            'file' => $relResultFilePath,
            'groups' => $data,
        ];
        $spriteName = $this->getSpriteName($sourceDir);
        return [$spriteName, $spriteData];
    }

    /**
     * @param string $sourceDir
     * @return string
     */
    private function getSchemeFilePath($sourceDir)
    {
        $baseDirName = basename($sourceDir);
        $fileName = substr($baseDirName, 0, strpos($baseDirName, '.')) . '.json';
        return "{$sourceDir}/{$fileName}";
    }

    /**
     * @param string $sourceDir
     * @return string
     */
    private function getResourcesDir($sourceDir)
    {
        $baseDirName = basename($sourceDir);
        $fileName = substr($baseDirName, 0, strpos($baseDirName, '.')) . '.dir';
        return "{$sourceDir}/{$fileName}";
    }

    /**
     * @param string $schemeFilePath
     * @return array $scheme [
     *   [
     *     'format': int $format,
     *     'type': int $type,
     *     'sequences': [
     *       {
     *         'frames': string[] $frameFileNames,
     *         'group': int $groupId
     *       }
     *     ]
     *   ]
     * ]
     */
    private function loadScheme($schemeFilePath)
    {
        $data = json_decode(file_get_contents($schemeFilePath), true);
        foreach ($data['sequences'] as &$sequence) {
            foreach ($sequence['frames'] as &$frame) {
                $frame = basename($frame);
            }
        }
        return $data;
    }

    /**
     * @param array $scheme
     * @param string $sourceDir
     * @param string $resultDir
     * @return array [string $absolutePath, string $relativePath]
     */
    private function getResultFilePath($scheme, $sourceDir, $resultDir)
    {
        $baseDirName = basename($sourceDir);
        $baseDirName = substr($baseDirName, 0, strpos($baseDirName, '.'));
        $fileName = "{$baseDirName}.png";
        $subDir = $scheme['format'];
        $relPath = "{$subDir}/{$fileName}";
        $absPath = "{$resultDir}/{$relPath}";
        return [$absPath, $relPath];
    }

    /**
     * @param string $sourceDir
     * @return string
     */
    private function getSpriteName($sourceDir)
    {
        $baseDirName = basename($sourceDir);
        return substr($baseDirName, 0, strpos($baseDirName, '.'));
    }

    /**
     * @param array $scheme
     * @param string $sourceDir
     * @param string $resultFilePath
     * @return array [
     *   int $groupId => [
     *     [int $offsetX, int $offsetY]
     *   ]
     * ]
     */
    private function generateSprite($scheme, $sourceDir, $resultFilePath)
    {
        $frames = [];
        $data = [];
        foreach ($scheme['sequences'] as $sequence) {
            $groupId = $sequence['group'];
            $data[$groupId] = [];
            foreach ($sequence['frames'] as $frameName) {
                $data[$groupId][$frameName] =
                $filePath = "{$sourceDir}/{$frameName}";
                $data[$groupId][$frameName] = $frameName;
                $frames[$frameName] = imagecreatefrompng($filePath);
            }
        }

        $resultImageDir = dirname($resultFilePath);
        if (!file_exists($resultImageDir)) {
            mkdir($resultImageDir, null, true);
        }
        $offsets = $this->unionImages($frames, $resultFilePath);

        $result = [];
        foreach ($data as $groupId => $groupData) {
            $result[$groupId] = [];
            foreach ($groupData as $frameName => $frameData) {
                $result[$groupId][] = $offsets[$frameName];
            }
        }
        return $result;
    }

    /**
     * @param array $images [string $name => resource $imageResource]
     * @param string $resultFilePath
     * @return array [
     *   string $imageName => [int $offsetX, int $offsetY]
     * ]
     */
    private function unionImages($images, $resultFilePath)
    {
        $firstImage = reset($images);
        $frameWidth = imagesx($firstImage);
        $frameHeight = imagesy($firstImage);

        list($width, $height) = $this->getSpriteSizeInFrames(count($images));
        $resultImage = imagecreate($frameWidth * $width, $frameHeight * $height);
        $data = [];
        $x = 0;
        $y = 0;
        foreach ($images as $name => $image) {
            if ($y == $height) {
                $x++;
                $y = 0;
            }
            $offsetX = $x * $frameWidth;
            $offsetY = $y * $frameHeight;
            imagecopy($resultImage, $image, $offsetX, $offsetY, 0, 0, $frameWidth, $frameHeight);
            $data[$name] = [$offsetX, $offsetY];
            $y++;
        }
        imagepng($resultImage, $resultFilePath);
        return $data;
    }

    /**
     * @param int $framesCount
     * @return int[] [int $width, int $height]
     */
    private function getSpriteSizeInFrames($framesCount)
    {
        $width = floor(sqrt($framesCount));
        while ($width < $framesCount) {
            if (($framesCount % $width) == 0) {
                break;
            }
            $width++;
        }
        $height = $framesCount / $width;
        return [$width, $height];
    }
}