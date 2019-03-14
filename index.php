<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<canvas id="canvas" height="700px" width="700px"></canvas>
<script>
    let data = {
            "ADcfra": {"width": 32, "height": 32, "groups": {"default": [0, 32, 64, 96, 128, 160, 192, 224]}, "path": "map-object\/ADcfra.png"},
            "AH01_": {"width": 96, "height": 64, "groups": {"up": [0], "up-right": [64], "right": [128], "down-right": [192], "down": [256], "move-up": [320, 384, 448, 512, 576, 640, 704, 768], "move-up-right": [832, 896, 960, 1024, 1088, 1152, 1216, 1280], "move-right": [1344, 1408, 1472, 1536, 1600, 1664, 1728, 1792], "move-down-right": [1856, 1920, 1984, 2048, 2112, 2176, 2240, 2304], "move-down": [2368, 2432, 2496, 2560, 2624, 2688, 2752, 2816]}, "path": "hero\/AH01_.png"},
            "AVLvol30": {"width": 96, "height": 96, "groups": {"default": [0, 96, 192, 288, 384, 480, 576, 672]}, "path": "map-object\/AVLvol30.png"},
            "AVXpsSN": {"width": 96, "height": 96, "groups": {"default": [0, 96, 192, 288, 384, 480, 576, 672]}, "path": "map-object\/AVXpsSN.png"},
            "adag": {"width": 32, "height": 32, "groups": {"default": [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 480, 512, 544, 576, 608, 640, 672, 704, 736, 768, 800, 832, 864, 896, 928, 960, 992, 1024, 1056, 1088, 1120, 1152, 1184, 1216, 1248, 1280, 1312, 1344, 1376, 1408, 1440, 1472, 1504, 1536, 1568]}, "path": "terrain\/adag.png"},
            "rock": {"width": 32, "height": 32, "groups": {"default": [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 480, 512, 544, 576, 608, 640, 672, 704, 736, 768, 800, 832, 864, 896, 928, 960, 992, 1024, 1056, 1088, 1120, 1152, 1184, 1216, 1248, 1280, 1312, 1344, 1376, 1408, 1440, 1472, 1504]}, "path": "terrain\/rock.png"},
            "sand": {"width": 32, "height": 32, "groups": {"default": [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 480, 512, 544, 576, 608, 640, 672, 704, 736]}, "path": "terrain\/sand.png"}
        }
    ;
    land = [
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 24},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 26},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 24},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 26},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'rock', 'sprite': 8},
            {'obj': 'rock', 'sprite': 10},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 26},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'rock', 'sprite': 8},
            {'obj': 'rock', 'sprite': 10},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 14},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 26},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 14},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 23},
            {'obj': 'rock', 'sprite': 26},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 28},
            {'obj': 'rock', 'sprite': 10},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 28},
            {'obj': 'rock', 'sprite': 10},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 18},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 12},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 23},
            {'obj': 'rock', 'sprite': 22},
            {'obj': 'rock', 'sprite': 14},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 24},
            {'obj': 'rock', 'sprite': 14},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 2},
            {'obj': 'sand', 'sprite': 2},
            {'obj': 'sand', 'sprite': 2},
            {'obj': 'sand', 'sprite': 0},
            {'obj': 'sand', 'sprite': 2},
            {'obj': 'sand', 'sprite': 3},
            {'obj': 'sand', 'sprite': 4},
            {'obj': 'sand', 'sprite': 5},
            {'obj': 'rock', 'sprite': 16},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 28},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 21},
            {'obj': 'rock', 'sprite': 20},
            {'obj': 'rock', 'sprite': 30},
            {'obj': 'rock', 'sprite': 0},
        ],
        [
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
            {'obj': 'rock', 'sprite': 0},
        ],
    ];
    var items = [
        [2, 15, 'AVXpsSN', 'default', null],
        [5, 14, 'ADcfra', 'default', null],
        [3, 4, 'AVLvol30', 'default', null],
        [6, 4, 'AH01_', 'right', null],
        [7, 6, 'adag', 'default', 11],
        [7, 7, 'adag', 'default', 11],
        [7, 8, 'adag', 'default', 11],
        [7, 9, 'adag', 'default', 11],
        [7, 10, 'adag', 'default', 11],
        [7, 11, 'adag', 'default', 11],
        [7, 12, 'adag', 'default', 11],
        [7, 13, 'adag', 'default', 2],
        [6, 14, 'adag', 'default', 35],
        [5, 15, 'adag', 'default', 26],
        [4, 15, 'adag', 'default', 25],
    ];
    var spriteNumber = [];
    var frameDuration = 10;
    var iteration = 0;
    var ctx = document.getElementById('canvas').getContext('2d');
    var objects = {};
    Object.entries(data).forEach(entry => {
        let key = entry[0];
        let value = entry[1];
        let image = new Image();
        image.src = value.path;
        objects[key] = {img: image};
        spriteNumber.push(0);
    });

    window.requestAnimationFrame(draw);

    function draw() {
        if (iteration % frameDuration !== 0) {
            iteration++;
            window.requestAnimationFrame(draw);
            return;
        }
        for (let y = 0; y < land.length; y++) {
            for (let x = 0; x < land[y].length; x++) {
                let posX = x * 32;
                let poxY = y * 32;
                let item = land[y][x];
                let sprite = objects[item.obj];
                let spriteOffset = data[item.obj].groups.default[item.sprite];
                ctx.drawImage(sprite.img, 0, spriteOffset, 32, 32, posX, poxY, 32, 32);

            }
        }
        for (let i = 0; i < items.length; i++) {
            let item = items[i];
            let posX = item[1] * 32;
            let poxY = item[0] * 32;
            let sprite = objects[item[2]];
            let itemData = data[item[2]];
            let spriteNum = item[4];
            if(spriteNum===null) {
                if (spriteNumber[i] >= itemData.groups[item[3]].length) {
                    spriteNumber[i] = 0;
                }
                spriteNum=spriteNumber[i];
            }
            let spriteOffset = itemData.groups[item[3]][spriteNum];
            ctx.drawImage(sprite.img, 0, spriteOffset, itemData.width, itemData.height, posX, poxY, itemData.width, itemData.height);


            if(item[4]===null) {
                spriteNumber[i]++
            }
        }

        window.requestAnimationFrame(draw);
        iteration++;
    }

</script>
</body>
</html>