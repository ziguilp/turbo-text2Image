<?php

namespace Turbo\text2Image;

class text2Image
{

    protected $font_file = __DIR__ . '/../fonts/SourceHanSans-Regular.ttf';

    protected $font_size = 12;

    protected $pt_size = 0;

    protected $color = "#333333";

    protected $width = null;

    protected $height = null;

    protected $shadow = null;

    protected $text = '';

    public $image = null;

    public function __construct(int $width = null, int $height = null)
    {
        if (!function_exists('imagettftext')) {
            throw new \Exception('Imagettftext is not enabled in your version of PHP');
        }
        $this->width = $width;
        $this->height = $height;
        $this->pt_size = ($this->font_size / 96) * 72; // Convert px to pt (72pt per inch, 96px per inch);

    }

    public function setFontFile($font)
    {
        if (is_file($font)) {
            $this->font_file = $font;
        }
        return $this;
    }

    public function setFontSize(int $fontSize)
    {
        $this->font_size = $fontSize;
        $this->pt_size = ($this->font_size / 96) * 72; // Convert px to pt (72pt per inch, 96px per inch);
        return $this;
    }

    public function setShadow(array $shadow)
    {
        $this->shadow = $shadow;
        return $this;
    }

    public function setColor(string $color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * 字符串分段检测
     */
    protected function checkText()
    {
        $text = $this->text;
        if (!$this->width) {
            return $text;
        }

        $box = imagettfbbox($this->pt_size, 0, $this->font_file, $text);
        if (!$box) {
            throw new \Exception("Unable to load font file: {$this->font_file}");
        }

        $boxWidth = abs($box[6] - $box[2]);

        $words_arr = [];

        if ($boxWidth > $this->width) {

            $text_arr = explode("\n", $text);

            foreach ($text_arr as $line => $txt) {

                $box = imagettfbbox($this->pt_size, 0, $this->font_file, $txt);
                $boxWidth = abs($box[6] - $box[2]);

                if ($boxWidth > $this->width) {
                    $this->splitLineTxt($txt, $words_arr);
                } else {
                    $words_arr[] = $txt;
                }
            }

            return implode("\n",  $words_arr);
        }
        return $text;
    }

    protected function splitLineTxt($txt, &$words_arr)
    {
        $lines = [];
        $line_txt = "";
        $line_num = 0;
        $num = mb_strlen($txt);
        for ($i = 0; $i < $num; $i++) {
            $word = mb_substr($txt, $i, 1);
            $box = imagettfbbox($this->pt_size, 0, $this->font_file, $line_txt . $word);
            $boxWidth = abs($box[6] - $box[2]);
            if ($boxWidth + abs($box[0]) < $this->width) {
                $line_txt .= $word;
                if ($i == $num - 1) {
                    array_push($words_arr, $line_txt);
                }
            } else {
                $lines[$line_num] = $line_txt;
                array_push($words_arr, $line_txt);
                $line_num++;
                $line_txt = $word;
            }
        }
        return $lines;
    }

    /**
     * 生成图片
     */
    public function generate($text, &$bound = null)
    {
        $this->text = $text;

        // BEGIN
        $text = $this->checkText();

        $angle = 0;

        $box = imagettfbbox($this->pt_size, 0, $this->font_file, $text);

        $x = 0;
        $y = 0 - $box[7];

        if (!$this->width) {
            $this->width = abs($box[6] - $box[2]);
        }

        if (!$this->height) {
            $this->height = abs($box[7] - $box[1]) + $y / 2;
        }

        $this->bgImage();

        // Text shadow
        if (is_array($this->shadow)) {
            imagettftext(
                $this->image,
                $this->pt_size,
                $angle,
                $x + $this->shadow['x'],
                $y + $this->shadow['y'],
                $this->allocateColor(isset($this->shadow['color']) ? $this->shadow['color'] : $this->color),
                $this->font_file,
                $text
            );
        }

        // Draw the text
        imagettftext($this->image, $this->pt_size, $angle, $x, $y, $this->allocateColor($this->color), $this->font_file, $text);

        $bound = [
            'width' => abs($box[6] - $box[2]),
            'height' => abs($box[6] - $box[2]),
        ];
        return $this;
    }

    public function save($filename)
    {
        imagesavealpha($this->image, true);
        imagepng($this->image, $filename);
    }

    protected function bgImage()
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        if (!$image) {
            throw new \Exception("Unable to create image container");
        }
        $tranparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $tranparent);
        // imagecolortransparent($image,$tranparent);
        $this->image = $image;
    }

    public static function normalizeColor($color)
    {
        // 140 CSS color names and hex values
        $cssColors = [
            'aliceblue' => '#f0f8ff', 'antiquewhite' => '#faebd7', 'aqua' => '#00ffff',
            'aquamarine' => '#7fffd4', 'azure' => '#f0ffff', 'beige' => '#f5f5dc', 'bisque' => '#ffe4c4',
            'black' => '#000000', 'blanchedalmond' => '#ffebcd', 'blue' => '#0000ff',
            'blueviolet' => '#8a2be2', 'brown' => '#a52a2a', 'burlywood' => '#deb887',
            'cadetblue' => '#5f9ea0', 'chartreuse' => '#7fff00', 'chocolate' => '#d2691e',
            'coral' => '#ff7f50', 'cornflowerblue' => '#6495ed', 'cornsilk' => '#fff8dc',
            'crimson' => '#dc143c', 'cyan' => '#00ffff', 'darkblue' => '#00008b', 'darkcyan' => '#008b8b',
            'darkgoldenrod' => '#b8860b', 'darkgray' => '#a9a9a9', 'darkgrey' => '#a9a9a9',
            'darkgreen' => '#006400', 'darkkhaki' => '#bdb76b', 'darkmagenta' => '#8b008b',
            'darkolivegreen' => '#556b2f', 'darkorange' => '#ff8c00', 'darkorchid' => '#9932cc',
            'darkred' => '#8b0000', 'darksalmon' => '#e9967a', 'darkseagreen' => '#8fbc8f',
            'darkslateblue' => '#483d8b', 'darkslategray' => '#2f4f4f', 'darkslategrey' => '#2f4f4f',
            'darkturquoise' => '#00ced1', 'darkviolet' => '#9400d3', 'deeppink' => '#ff1493',
            'deepskyblue' => '#00bfff', 'dimgray' => '#696969', 'dimgrey' => '#696969',
            'dodgerblue' => '#1e90ff', 'firebrick' => '#b22222', 'floralwhite' => '#fffaf0',
            'forestgreen' => '#228b22', 'fuchsia' => '#ff00ff', 'gainsboro' => '#dcdcdc',
            'ghostwhite' => '#f8f8ff', 'gold' => '#ffd700', 'goldenrod' => '#daa520', 'gray' => '#808080',
            'grey' => '#808080', 'green' => '#008000', 'greenyellow' => '#adff2f',
            'honeydew' => '#f0fff0', 'hotpink' => '#ff69b4', 'indianred ' => '#cd5c5c',
            'indigo ' => '#4b0082', 'ivory' => '#fffff0', 'khaki' => '#f0e68c', 'lavender' => '#e6e6fa',
            'lavenderblush' => '#fff0f5', 'lawngreen' => '#7cfc00', 'lemonchiffon' => '#fffacd',
            'lightblue' => '#add8e6', 'lightcoral' => '#f08080', 'lightcyan' => '#e0ffff',
            'lightgoldenrodyellow' => '#fafad2', 'lightgray' => '#d3d3d3', 'lightgrey' => '#d3d3d3',
            'lightgreen' => '#90ee90', 'lightpink' => '#ffb6c1', 'lightsalmon' => '#ffa07a',
            'lightseagreen' => '#20b2aa', 'lightskyblue' => '#87cefa', 'lightslategray' => '#778899',
            'lightslategrey' => '#778899', 'lightsteelblue' => '#b0c4de', 'lightyellow' => '#ffffe0',
            'lime' => '#00ff00', 'limegreen' => '#32cd32', 'linen' => '#faf0e6', 'magenta' => '#ff00ff',
            'maroon' => '#800000', 'mediumaquamarine' => '#66cdaa', 'mediumblue' => '#0000cd',
            'mediumorchid' => '#ba55d3', 'mediumpurple' => '#9370db', 'mediumseagreen' => '#3cb371',
            'mediumslateblue' => '#7b68ee', 'mediumspringgreen' => '#00fa9a',
            'mediumturquoise' => '#48d1cc', 'mediumvioletred' => '#c71585', 'midnightblue' => '#191970',
            'mintcream' => '#f5fffa', 'mistyrose' => '#ffe4e1', 'moccasin' => '#ffe4b5',
            'navajowhite' => '#ffdead', 'navy' => '#000080', 'oldlace' => '#fdf5e6', 'olive' => '#808000',
            'olivedrab' => '#6b8e23', 'orange' => '#ffa500', 'orangered' => '#ff4500',
            'orchid' => '#da70d6', 'palegoldenrod' => '#eee8aa', 'palegreen' => '#98fb98',
            'paleturquoise' => '#afeeee', 'palevioletred' => '#db7093', 'papayawhip' => '#ffefd5',
            'peachpuff' => '#ffdab9', 'peru' => '#cd853f', 'pink' => '#ffc0cb', 'plum' => '#dda0dd',
            'powderblue' => '#b0e0e6', 'purple' => '#800080', 'rebeccapurple' => '#663399',
            'red' => '#ff0000', 'rosybrown' => '#bc8f8f', 'royalblue' => '#4169e1',
            'saddlebrown' => '#8b4513', 'salmon' => '#fa8072', 'sandybrown' => '#f4a460',
            'seagreen' => '#2e8b57', 'seashell' => '#fff5ee', 'sienna' => '#a0522d',
            'silver' => '#c0c0c0', 'skyblue' => '#87ceeb', 'slateblue' => '#6a5acd',
            'slategray' => '#708090', 'slategrey' => '#708090', 'snow' => '#fffafa',
            'springgreen' => '#00ff7f', 'steelblue' => '#4682b4', 'tan' => '#d2b48c', 'teal' => '#008080',
            'thistle' => '#d8bfd8', 'tomato' => '#ff6347', 'turquoise' => '#40e0d0',
            'violet' => '#ee82ee', 'wheat' => '#f5deb3', 'white' => '#ffffff', 'whitesmoke' => '#f5f5f5',
            'yellow' => '#ffff00', 'yellowgreen' => '#9acd32'
        ];

        // Parse alpha from '#fff|.5' and 'white|.5'
        if (is_string($color) && strstr($color, '|')) {
            $color = explode('|', $color);
            $alpha = (float) $color[1];
            $color = trim($color[0]);
        } else {
            $alpha = 1;
        }

        // Translate CSS color names to hex values
        if (is_string($color) && array_key_exists(strtolower($color), $cssColors)) {
            $color = $cssColors[strtolower($color)];
        }

        // Translate transparent keyword to a transparent color
        if ($color === 'transparent') {
            $color = ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0];
        }

        // Convert hex values to RGBA
        if (is_string($color)) {
            // Remove #
            $hex = preg_replace('/^#/', '', $color);

            // Support short and standard hex codes
            if (strlen($hex) === 3) {
                list($red, $green, $blue) = [
                    $hex[0] . $hex[0],
                    $hex[1] . $hex[1],
                    $hex[2] . $hex[2]
                ];
            } elseif (strlen($hex) === 6) {
                list($red, $green, $blue) = [
                    $hex[0] . $hex[1],
                    $hex[2] . $hex[3],
                    $hex[4] . $hex[5]
                ];
            } else {
                throw new \Exception("Invalid color value: $color");
            }

            $color = [
                'red' => hexdec($red),
                'green' => hexdec($green),
                'blue' => hexdec($blue),
                'alpha' => $alpha
            ];
        }

        // Enforce color value ranges
        if (is_array($color)) {
            // RGB default to 0
            $color['red'] = isset($color['red']) ? $color['red'] : 0;
            $color['green'] = isset($color['green']) ? $color['green'] : 0;
            $color['blue'] = isset($color['blue']) ? $color['blue'] : 0;

            // Alpha defaults to 1
            $color['alpha'] = isset($color['alpha']) ? $color['alpha'] : 1;

            return [
                'red' => (int) self::keepWithin((int) $color['red'], 0, 255),
                'green' => (int) self::keepWithin((int) $color['green'], 0, 255),
                'blue' => (int) self::keepWithin((int) $color['blue'], 0, 255),
                'alpha' => self::keepWithin($color['alpha'], 0, 1)
            ];
        }

        throw new \Exception("Invalid color value: $color");
    }

    protected static function keepWithin($value, $min, $max)
    {
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    }

    protected function allocateColor($color)
    {
        $color = self::normalizeColor($color);

        // Was this color already allocated?
        $index = imagecolorexactalpha(
            $this->image,
            $color['red'],
            $color['green'],
            $color['blue'],
            127 - ($color['alpha'] * 127)
        );
        if ($index > -1) {
            // Yes, return this color index
            return $index;
        }

        // Allocate a new color index
        return imagecolorallocatealpha(
            $this->image,
            $color['red'],
            $color['green'],
            $color['blue'],
            127 - ($color['alpha'] * 127)
        );
    }

    public function __destruct()
    {
        if ($this->image !== null && get_resource_type($this->image) === 'gd') {
            imagedestroy($this->image);
        }
    }
}
