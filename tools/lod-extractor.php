<?php

if (count($argv) != 3) {
    echo ".LOD files extractor.\n";
    echo "Usage: php./log-extractor.php <sourceFilePath> <resultDirectory>\n";
    echo "  * <sourceFilePath> - Path to a source .LOD file.\n";
    echo "  * <resultDirectory> - Path to directory with results.\n";
    return 1;
}
$sourceFile = $argv[1];
if (!file_exists($sourceFile)) {
    echo "Source file '{$sourceFile}' does not exist.\n";
    return 1;
}
$resultDirectory = $argv[2];
if (!file_exists($resultDirectory) || !is_dir($resultDirectory)) {
    echo "Result directory '{$resultDirectory}' does not exist.\n";
    return 1;
}

$result = (new Extractor())->extractFile($sourceFile, realpath($resultDirectory));
return $result ? 0 : 1;

class Extractor
{
    /**
     * @param string $sourceFile
     * @param string $resultDirectory
     * @return  bool
     */
    public function extractFile($sourceFile, $resultDirectory)
    {
        echo "Start to extract '{$sourceFile}' file to '{$resultDirectory}' directory\n";
        $lodReader = fopen($sourceFile, 'r');
        try {
            $header = fread($lodReader, 4);
            if ($header != "LOD\0") {
                echo "Error: Not a .LOD file.\n";
                return false;
            }
            list($lodReader, $files) = $this->getFilesList($lodReader);
            $this->extractFiles($lodReader, $files, $resultDirectory);
        } finally {
            fclose($lodReader);
        }
        return true;
    }

    /**
     * @param resource $lodReader
     * @return array[resource $lodReader, array $filesList]
     * Each file is array [string $fileName, int $offset, int $size, int $csize]
     */
    private function getFilesList($lodReader)
    {
        fseek($lodReader, 8);
        $total = unpack('I', fread($lodReader, 4))[1];
        fseek($lodReader, 92);
        $files = [];
        for ($i = 0; $i < $total; $i++) {
            $filename = fread($lodReader, 16);
            $filename = substr($filename, 0, strpos($filename, "\0"));
            $filename = strtolower($filename);
            $offset = unpack('I', fread($lodReader, 4))[1];
            $size = unpack('I', fread($lodReader, 4))[1];
            fread($lodReader, 4);
            $csize = unpack('I', fread($lodReader, 4))[1];
            $files[] = ['filename' => $filename, 'offset' => $offset, 'size' => $size, 'csize' => $csize];
        }
        return [$lodReader, $files];
    }

    /**
     * @param resource $lodReader
     * @param array $files
     * @param string $resultDirectory
     * @throws Exception
     */
    private function extractFiles($lodReader, $files, $resultDirectory)
    {
        $filesCount = count($files);
        foreach ($files as $i => $file) {
            echo '  [' . ($i + 1) . '/' . $filesCount . '] ' . $file['filename'] . "\n";
            fseek($lodReader, $file['offset']);
            if ($file['csize'] == 0) {
                $data = fread($lodReader, $file['size']);
            } else {
                $data = zlib_decode(fread($lodReader, $file['csize']));
            }
            if ($this->isPcx($data)) {
                throw new \Exception("Not implemented!");
                /*
                im = read_pcx(data)
                if not im:
                    return False
                filename = os.path.splitext(filename)[0]
                filename = filename+".png"
                im.save(filename)
                 */
            } else {
                $fileWriter = fopen("{$resultDirectory}/{$file['filename']}", 'w');
                try {
                    fwrite($fileWriter, $data);
                } finally {
                    fclose($fileWriter);
                }
            }
        }
    }

    /**
     * @param string $data
     * @return bool
     */
    private function isPcx($data)
    {
        $meta = unpack('Isize/Iwidth/Iheight', substr($data, 0, 12));
        if ($meta['size'] == $meta['width'] * $meta['height']) {
            return true;
        }
        if ($meta['size'] == $meta['width'] * $meta['height'] * 3) {
            return true;
        }
        return false;
    }

    private function readPcx($data)
    {
        throw new \Exception("Not implemented!");
        /*
         def read_pcx(data):
             size,width,height = struct.unpack("<III",data[:12])
             if size == width*height:
                 im = Image.fromstring('P', (width,height),data[12:12+width*height])
                 palette = []
                 for i in range(256):
                     offset=12+width*height+i*3
                     r,g,b = struct.unpack("<BBB",data[offset:offset+3])
                     palette.extend((r,g,b))
                 im.putpalette(palette)
                 return im
             elif size == width*height*3:
                 return Image.fromstring('RGB', (width,height),data[12:])
             else:
                 return None
         */
    }
}