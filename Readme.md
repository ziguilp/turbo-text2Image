### 支持中文，文字转图片，支持自动断行，加阴影

#### 使用说明

```
composer require turbo-text2image
```

```php
use Turbo\text2Image\text2Image;

// 指定容器宽高
$width = 800;
$height = 600;
// 当不指定宽时，将不自动断行
// 当指定高时，文字超出部分将不会显示
$image = new text2Image($width, $height);

$text = <<<MSG_EOF
<?php
// Set the enviroment variable for GD
putenv('GDFONTPATH=' . realpath('.'));

// Name the font to be used (note the lack of the .ttf extension)
//  = SomeFont;
?>
MSG_EOF;

$image->setFontSize(30)->setColor("red")->setShadow([
    'x'=> 2,
    'y'=> 2,
    'color' => "#0ff|0.8"
])->generate($text)
    ->save('1.png');

```
