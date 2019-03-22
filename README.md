## Tools

### Original resource extractor

Extract resource files of original game.

1. Install `python 2.7`
1.1 Install python packages `pillow` and `numpy`
2. Install PHP 5.5+
3. Fill settings in `/tools/original-resources-extractor/original-resources-extractor.config.json`
4. Put original files form `Data` game directory to `sourceDir` directory
4. Run `/tools/original-resources-extractor/original-resources-extractor.php` script
5. Waite for a long time while script extract files
6. Result files will be placed into `resultDir` directory
