<?php

/**
 * Author: Arlo Carreon <http://arlocarreon.com>
 * Info: http://mexitek.github.io/phpColors/
 * License: http://arlo.mit-license.org/
 */

namespace Mexitek\PHPColors;

use Exception;

/**
 * A color utility that helps manipulate HEX colors
 */
class Color
{
    /**
     * @var string
     */
    private $_hex;

    /**
     * @var array
     */
    private $_hsl;

    /**
     * @var array
     */
    private $_rgb;

    /**
     * Auto darkens/lightens by 10% for sexily-subtle gradients.
     * Set this to FALSE to adjust automatic shade to be between given color
     * and black (for darken) or white (for lighten)
     */
    public const DEFAULT_ADJUST = 10;

    /**
     * Instantiates the class with a HEX value
     * @param string $hex
     * @throws Exception
     */
    public function __construct(string $hex)
    {
        $color = self::sanitizeHex($hex);
        $this->_hex = $color;
        $this->_hsl = self::hexToHsl($color);
        $this->_rgb = self::hexToRgb($color);
    }

    /**
     * Given a HEX string returns a HSL array equivalent.
     * @param string $color
     * @return array HSL associative array
     * @throws Exception
     */
    public static function hexToHsl(string $color): array
    {
        // Sanity check
        $color = self::sanitizeHex($color);

        // Convert HEX to DEC
        $R = hexdec($color[0] . $color[1]);
        $G = hexdec($color[2] . $color[3]);
        $B = hexdec($color[4] . $color[5]);

        $HSL = array();

        $var_R = ($R / 255);
        $var_G = ($G / 255);
        $var_B = ($B / 255);

        $var_Min = min($var_R, $var_G, $var_B);
        $var_Max = max($var_R, $var_G, $var_B);
        $del_Max = $var_Max - $var_Min;

        $L = ($var_Max + $var_Min) / 2;

        if ($del_Max == 0) {
            $H = 0;
            $S = 0;
        } else {
            if ($L < 0.5) {
                $S = $del_Max / ($var_Max + $var_Min);
            } else {
                $S = $del_Max / (2 - $var_Max - $var_Min);
            }

            $del_R = ((($var_Max - $var_R) / 6) + ($del_Max / 2)) / $del_Max;
            $del_G = ((($var_Max - $var_G) / 6) + ($del_Max / 2)) / $del_Max;
            $del_B = ((($var_Max - $var_B) / 6) + ($del_Max / 2)) / $del_Max;

            if ($var_R == $var_Max) {
                $H = $del_B - $del_G;
            } elseif ($var_G == $var_Max) {
                $H = (1 / 3) + $del_R - $del_B;
            } elseif ($var_B == $var_Max) {
                $H = (2 / 3) + $del_G - $del_R;
            }

            if ($H < 0) {
                $H++;
            }
            if ($H > 1) {
                $H--;
            }
        }

        $HSL['H'] = ($H * 360);
        $HSL['S'] = $S;
        $HSL['L'] = $L;

        return $HSL;
    }

    /**
     * Given a HSL associative array returns the equivalent HEX string
     * @param array $hsl
     * @return string HEX string
     * @throws Exception "Bad HSL Array"
     */
    public static function hslToHex(array $hsl = array()): string
    {
        // Make sure it's HSL
        if (empty($hsl) || !isset($hsl["H"], $hsl["S"], $hsl["L"])) {
            throw new Exception("Param was not an HSL array");
        }

        list($H, $S, $L) = array($hsl['H'] / 360, $hsl['S'], $hsl['L']);

        if ($S == 0) {
            $r = $L * 255;
            $g = $L * 255;
            $b = $L * 255;
        } else {
            if ($L < 0.5) {
                $var_2 = $L * (1 + $S);
            } else {
                $var_2 = ($L + $S) - ($S * $L);
            }

            $var_1 = 2 * $L - $var_2;

            $r = 255 * self::hueToRgb($var_1, $var_2, $H + (1 / 3));
            $g = 255 * self::hueToRgb($var_1, $var_2, $H);
            $b = 255 * self::hueToRgb($var_1, $var_2, $H - (1 / 3));
        }

        // Convert to hex
        $r = dechex(round($r));
        $g = dechex(round($g));
        $b = dechex(round($b));

        // Make sure we get 2 digits for decimals
        $r = (strlen("" . $r) === 1) ? "0" . $r : $r;
        $g = (strlen("" . $g) === 1) ? "0" . $g : $g;
        $b = (strlen("" . $b) === 1) ? "0" . $b : $b;

        return $r . $g . $b;
    }


    /**
     * Given a HEX string returns a RGB array equivalent.
     * @param string $color
     * @return array RGB associative array
     * @throws Exception
     */
    public static function hexToRgb(string $color): array
    {
        // Sanity check
        $color = self::sanitizeHex($color);

        // Convert HEX to DEC
        $R = hexdec($color[0] . $color[1]);
        $G = hexdec($color[2] . $color[3]);
        $B = hexdec($color[4] . $color[5]);

        $RGB['R'] = $R;
        $RGB['G'] = $G;
        $RGB['B'] = $B;

        return $RGB;
    }


    /**
     * Given an RGB associative array returns the equivalent HEX string
     * @param array $rgb
     * @return string Hex string
     * @throws Exception "Bad RGB Array"
     */
    public static function rgbToHex(array $rgb = array()): string
    {
        // Make sure it's RGB
        if (empty($rgb) || !isset($rgb["R"], $rgb["G"], $rgb["B"])) {
            throw new Exception("Param was not an RGB array");
        }

        // https://github.com/mexitek/phpColors/issues/25#issuecomment-88354815
        // Convert RGB to HEX
        $hex[0] = str_pad(dechex((int)$rgb['R']), 2, '0', STR_PAD_LEFT);
        $hex[1] = str_pad(dechex((int)$rgb['G']), 2, '0', STR_PAD_LEFT);
        $hex[2] = str_pad(dechex((int)$rgb['B']), 2, '0', STR_PAD_LEFT);

        // Make sure that 2 digits are allocated to each color.
        $hex[0] = (strlen($hex[0]) === 1) ? '0' . $hex[0] : $hex[0];
        $hex[1] = (strlen($hex[1]) === 1) ? '0' . $hex[1] : $hex[1];
        $hex[2] = (strlen($hex[2]) === 1) ? '0' . $hex[2] : $hex[2];

        return implode('', $hex);
    }

    /**
     * Given an RGB associative array, returns CSS string output.
     * @param array $rgb
     * @return string rgb(r,g,b) string
     * @throws Exception "Bad RGB Array"
     */
    public static function rgbToString(array $rgb = array()): string
    {
        // Make sure it's RGB
        if (empty($rgb) || !isset($rgb["R"], $rgb["G"], $rgb["B"])) {
            throw new Exception("Param was not an RGB array");
        }

        return 'rgb(' .
            $rgb['R'] . ', ' .
            $rgb['G'] . ', ' .
            $rgb['B'] . ')';
    }

    /**
     * Given a standard color name, return hex code
     *
     * @param string $color_name
     * @return string
     */
    public static function nameToHex(string $color_name): string
    {
        $colors = array(
            'aliceblue' => 'F0F8FF',
            'antiquewhite' => 'FAEBD7',
            'aqua' => '00FFFF',
            'aquamarine' => '7FFFD4',
            'azure' => 'F0FFFF',
            'beige' => 'F5F5DC',
            'bisque' => 'FFE4C4',
            'black' => '000000',
            'blanchedalmond' => 'FFEBCD',
            'blue' => '0000FF',
            'blueviolet' => '8A2BE2',
            'brown' => 'A52A2A',
            'burlywood' => 'DEB887',
            'cadetblue' => '5F9EA0',
            'chartreuse' => '7FFF00',
            'chocolate' => 'D2691E',
            'coral' => 'FF7F50',
            'cornflowerblue' => '6495ED',
            'cornsilk' => 'FFF8DC',
            'crimson' => 'DC143C',
            'cyan' => '00FFFF',
            'darkblue' => '00008B',
            'darkcyan' => '008B8B',
            'darkgoldenrod' => 'B8860B',
            'darkgray' => 'A9A9A9',
            'darkgreen' => '006400',
            'darkgrey' => 'A9A9A9',
            'darkkhaki' => 'BDB76B',
            'darkmagenta' => '8B008B',
            'darkolivegreen' => '556B2F',
            'darkorange' => 'FF8C00',
            'darkorchid' => '9932CC',
            'darkred' => '8B0000',
            'darksalmon' => 'E9967A',
            'darkseagreen' => '8FBC8F',
            'darkslateblue' => '483D8B',
            'darkslategray' => '2F4F4F',
            'darkslategrey' => '2F4F4F',
            'darkturquoise' => '00CED1',
            'darkviolet' => '9400D3',
            'deeppink' => 'FF1493',
            'deepskyblue' => '00BFFF',
            'dimgray' => '696969',
            'dimgrey' => '696969',
            'dodgerblue' => '1E90FF',
            'firebrick' => 'B22222',
            'floralwhite' => 'FFFAF0',
            'forestgreen' => '228B22',
            'fuchsia' => 'FF00FF',
            'gainsboro' => 'DCDCDC',
            'ghostwhite' => 'F8F8FF',
            'gold' => 'FFD700',
            'goldenrod' => 'DAA520',
            'gray' => '808080',
            'green' => '008000',
            'greenyellow' => 'ADFF2F',
            'grey' => '808080',
            'honeydew' => 'F0FFF0',
            'hotpink' => 'FF69B4',
            'indianred' => 'CD5C5C',
            'indigo' => '4B0082',
            'ivory' => 'FFFFF0',
            'khaki' => 'F0E68C',
            'lavender' => 'E6E6FA',
            'lavenderblush' => 'FFF0F5',
            'lawngreen' => '7CFC00',
            'lemonchiffon' => 'FFFACD',
            'lightblue' => 'ADD8E6',
            'lightcoral' => 'F08080',
            'lightcyan' => 'E0FFFF',
            'lightgoldenrodyellow' => 'FAFAD2',
            'lightgray' => 'D3D3D3',
            'lightgreen' => '90EE90',
            'lightgrey' => 'D3D3D3',
            'lightpink' => 'FFB6C1',
            'lightsalmon' => 'FFA07A',
            'lightseagreen' => '20B2AA',
            'lightskyblue' => '87CEFA',
            'lightslategray' => '778899',
            'lightslategrey' => '778899',
            'lightsteelblue' => 'B0C4DE',
            'lightyellow' => 'FFFFE0',
            'lime' => '00FF00',
            'limegreen' => '32CD32',
            'linen' => 'FAF0E6',
            'magenta' => 'FF00FF',
            'maroon' => '800000',
            'mediumaquamarine' => '66CDAA',
            'mediumblue' => '0000CD',
            'mediumorchid' => 'BA55D3',
            'mediumpurple' => '9370D0',
            'mediumseagreen' => '3CB371',
            'mediumslateblue' => '7B68EE',
            'mediumspringgreen' => '00FA9A',
            'mediumturquoise' => '48D1CC',
            'mediumvioletred' => 'C71585',
            'midnightblue' => '191970',
            'mintcream' => 'F5FFFA',
            'mistyrose' => 'FFE4E1',
            'moccasin' => 'FFE4B5',
            'navajowhite' => 'FFDEAD',
            'navy' => '000080',
            'oldlace' => 'FDF5E6',
            'olive' => '808000',
            'olivedrab' => '6B8E23',
            'orange' => 'FFA500',
            'orangered' => 'FF4500',
            'orchid' => 'DA70D6',
            'palegoldenrod' => 'EEE8AA',
            'palegreen' => '98FB98',
            'paleturquoise' => 'AFEEEE',
            'palevioletred' => 'DB7093',
            'papayawhip' => 'FFEFD5',
            'peachpuff' => 'FFDAB9',
            'peru' => 'CD853F',
            'pink' => 'FFC0CB',
            'plum' => 'DDA0DD',
            'powderblue' => 'B0E0E6',
            'purple' => '800080',
            'red' => 'FF0000',
            'rosybrown' => 'BC8F8F',
            'royalblue' => '4169E1',
            'saddlebrown' => '8B4513',
            'salmon' => 'FA8072',
            'sandybrown' => 'F4A460',
            'seagreen' => '2E8B57',
            'seashell' => 'FFF5EE',
            'sienna' => 'A0522D',
            'silver' => 'C0C0C0',
            'skyblue' => '87CEEB',
            'slateblue' => '6A5ACD',
            'slategray' => '708090',
            'slategrey' => '708090',
            'snow' => 'FFFAFA',
            'springgreen' => '00FF7F',
            'steelblue' => '4682B4',
            'tan' => 'D2B48C',
            'teal' => '008080',
            'thistle' => 'D8BFD8',
            'tomato' => 'FF6347',
            'turquoise' => '40E0D0',
            'violet' => 'EE82EE',
            'wheat' => 'F5DEB3',
            'white' => 'FFFFFF',
            'whitesmoke' => 'F5F5F5',
            'yellow' => 'FFFF00',
            'yellowgreen' => '9ACD32'
        );

        $color_name = strtolower($color_name);
        if (isset($colors[$color_name])) {
            return '#' . $colors[$color_name];
        }

        return $color_name;
    }

    /**
     * Given a HEX value, returns a darker color. If no desired amount provided, then the color halfway between
     * given HEX and black will be returned.
     * @param int $amount
     * @return string Darker HEX value
     * @throws Exception
     */
    public function darken(int $amount = self::DEFAULT_ADJUST): string
    {
        // Darken
        $darkerHSL = $this->darkenHsl($this->_hsl, $amount);
        // Return as HEX
        return self::hslToHex($darkerHSL);
    }

    /**
     * Given a HEX value, returns a lighter color. If no desired amount provided, then the color halfway between
     * given HEX and white will be returned.
     * @param int $amount
     * @return string Lighter HEX value
     * @throws Exception
     */
    public function lighten(int $amount = self::DEFAULT_ADJUST): string
    {
        // Lighten
        $lighterHSL = $this->lightenHsl($this->_hsl, $amount);
        // Return as HEX
        return self::hslToHex($lighterHSL);
    }

    /**
     * Given a HEX value, returns a mixed color. If no desired amount provided, then the color mixed by this ratio
     * @param string $hex2 Secondary HEX value to mix with
     * @param int $amount = -100..0..+100
     * @return string mixed HEX value
     * @throws Exception
     */
    public function mix(string $hex2, int $amount = 0): string
    {
        $rgb2 = self::hexToRgb($hex2);
        $mixed = $this->mixRgb($this->_rgb, $rgb2, $amount);
        // Return as HEX
        return self::rgbToHex($mixed);
    }

    /**
     * Creates an array with two shades that can be used to make a gradient
     * @param int $amount Optional percentage amount you want your contrast color
     * @return array An array with a 'light' and 'dark' index
     * @throws Exception
     */
    public function makeGradient(int $amount = self::DEFAULT_ADJUST): array
    {
        // Decide which color needs to be made
        if ($this->isLight()) {
            $lightColor = $this->_hex;
            $darkColor = $this->darken($amount);
        } else {
            $lightColor = $this->lighten($amount);
            $darkColor = $this->_hex;
        }

        // Return our gradient array
        return array("light" => $lightColor, "dark" => $darkColor);
    }


    /**
     * Returns whether or not given color is considered "light"
     * @param string|bool $color
     * @param int $lighterThan
     * @return boolean
     */
    public function isLight($color = false, int $lighterThan = 130): bool
    {
        // Get our color
        $color = ($color) ? $color : $this->_hex;

        // Calculate straight from rbg
        $r = hexdec($color[0] . $color[1]);
        $g = hexdec($color[2] . $color[3]);
        $b = hexdec($color[4] . $color[5]);

        return (($r * 299 + $g * 587 + $b * 114) / 1000 > $lighterThan);
    }

    /**
     * Returns whether or not a given color is considered "dark"
     * @param string|bool $color
     * @param int $darkerThan
     * @return boolean
     */
    public function isDark($color = false, int $darkerThan = 130): bool
    {
        // Get our color
        $color = ($color) ? $color : $this->_hex;

        // Calculate straight from rbg
        $r = hexdec($color[0] . $color[1]);
        $g = hexdec($color[2] . $color[3]);
        $b = hexdec($color[4] . $color[5]);

        return (($r * 299 + $g * 587 + $b * 114) / 1000 <= $darkerThan);
    }

    /**
     * Returns the complimentary color
     * @return string Complementary hex color
     * @throws Exception
     */
    public function complementary(): string
    {
        // Get our HSL
        $hsl = $this->_hsl;

        // Adjust Hue 180 degrees
        $hsl['H'] += ($hsl['H'] > 180) ? -180 : 180;

        // Return the new value in HEX
        return self::hslToHex($hsl);
    }

    /**
     * Returns the HSL array of your color
     */
    public function getHsl(): array
    {
        return $this->_hsl;
    }

    /**
     * Returns your original color
     */
    public function getHex(): string
    {
        return $this->_hex;
    }

    /**
     * Returns the RGB array of your color
     */
    public function getRgb(): array
    {
        return $this->_rgb;
    }

    /**
     * Returns the cross browser CSS3 gradient
     * @param int $amount Optional: percentage amount to light/darken the gradient
     * @param boolean $vintageBrowsers Optional: include vendor prefixes for browsers that almost died out already
     * @param string $prefix Optional: prefix for every lines
     * @param string $suffix Optional: suffix for every lines
     * @return string CSS3 gradient for chrome, safari, firefox, opera and IE10
     * @throws Exception
     * @link http://caniuse.com/css-gradients Resource for the browser support
     */
    public function getCssGradient($amount = self::DEFAULT_ADJUST, $vintageBrowsers = false, $suffix = "", $prefix = ""): string
    {
        // Get the recommended gradient
        $g = $this->makeGradient($amount);

        $css = "";
        /* fallback/image non-cover color */
        $css .= "{$prefix}background-color: #" . $this->_hex . ";{$suffix}";

        /* IE Browsers */
        $css .= "{$prefix}filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#" . $g['light'] . "', endColorstr='#" . $g['dark'] . "');{$suffix}";

        /* Safari 4+, Chrome 1-9 */
        if ($vintageBrowsers) {
            $css .= "{$prefix}background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#" . $g['light'] . "), to(#" . $g['dark'] . "));{$suffix}";
        }

        /* Safari 5.1+, Mobile Safari, Chrome 10+ */
        $css .= "{$prefix}background-image: -webkit-linear-gradient(top, #" . $g['light'] . ", #" . $g['dark'] . ");{$suffix}";

        if ($vintageBrowsers) {
            /* Firefox 3.6+ */
            $css .= "{$prefix}background-image: -moz-linear-gradient(top, #" . $g['light'] . ", #" . $g['dark'] . ");{$suffix}";

            /* Opera 11.10+ */
            $css .= "{$prefix}background-image: -o-linear-gradient(top, #" . $g['light'] . ", #" . $g['dark'] . ");{$suffix}";
        }

        /* Unprefixed version (standards): FF 16+, IE10+, Chrome 26+, Safari 7+, Opera 12.1+ */
        $css .= "{$prefix}background-image: linear-gradient(to bottom, #" . $g['light'] . ", #" . $g['dark'] . ");{$suffix}";

        // Return our CSS
        return $css;
    }

    /**
     * Darkens a given HSL array
     * @param array $hsl
     * @param int $amount
     * @return array $hsl
     */
    private function darkenHsl(array $hsl, int $amount = self::DEFAULT_ADJUST): array
    {
        // Check if we were provided a number
        if ($amount) {
            $hsl['L'] = ($hsl['L'] * 100) - $amount;
            $hsl['L'] = ($hsl['L'] < 0) ? 0 : $hsl['L'] / 100;
        } else {
            // We need to find out how much to darken
            $hsl['L'] /= 2;
        }

        return $hsl;
    }

    /**
     * Lightens a given HSL array
     * @param array $hsl
     * @param int $amount
     * @return array
     */
    private function lightenHsl(array $hsl, int $amount = self::DEFAULT_ADJUST): array
    {
        // Check if we were provided a number
        if ($amount) {
            $hsl['L'] = ($hsl['L'] * 100) + $amount;
            $hsl['L'] = ($hsl['L'] > 100) ? 1 : $hsl['L'] / 100;
        } else {
            // We need to find out how much to lighten
            $hsl['L'] += (1 - $hsl['L']) / 2;
        }

        return $hsl;
    }

    /**
     * Mix two RGB colors and return the resulting RGB color
     * ported from http://phpxref.pagelines.com/nav.html?includes/class.colors.php.source.html
     * @param array $rgb1
     * @param array $rgb2
     * @param int $amount ranged -100..0..+100
     * @return array
     */
    private function mixRgb(array $rgb1, array $rgb2, int $amount = 0): array
    {
        $r1 = ($amount + 100) / 100;
        $r2 = 2 - $r1;

        $rmix = (($rgb1['R'] * $r1) + ($rgb2['R'] * $r2)) / 2;
        $gmix = (($rgb1['G'] * $r1) + ($rgb2['G'] * $r2)) / 2;
        $bmix = (($rgb1['B'] * $r1) + ($rgb2['B'] * $r2)) / 2;

        return array('R' => $rmix, 'G' => $gmix, 'B' => $bmix);
    }

    /**
     * Given a Hue, returns corresponding RGB value
     * @param float $v1
     * @param float $v2
     * @param float $vH
     * @return float
     */
    private static function hueToRgb(float $v1, float $v2, float $vH): float
    {
        if ($vH < 0) {
            ++$vH;
        }

        if ($vH > 1) {
            --$vH;
        }

        if ((6 * $vH) < 1) {
            return ($v1 + ($v2 - $v1) * 6 * $vH);
        }

        if ((2 * $vH) < 1) {
            return $v2;
        }

        if ((3 * $vH) < 2) {
            return ($v1 + ($v2 - $v1) * ((2 / 3) - $vH) * 6);
        }

        return $v1;
    }

    /**
     * Checks the HEX string for correct formatting and converts short format to long
     * @param string $hex
     * @return string
     * @throws Exception
     */
    private static function sanitizeHex(string $hex): string
    {
        // Strip # sign if it is present
        $color = str_replace("#", "", $hex);

        // Validate hex string
        if (!preg_match('/^[a-fA-F0-9]+$/', $color)) {
            throw new Exception("HEX color does not match format");
        }

        // Make sure it's 6 digits
        if (strlen($color) === 3) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        } elseif (strlen($color) !== 6) {
            throw new Exception("HEX color needs to be 6 or 3 digits long");
        }

        return $color;
    }

    /**
     * Converts object into its string representation
     * @return string
     */
    public function __toString()
    {
        return "#" . $this->getHex();
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        switch (strtolower($name)) {
            case 'red':
            case 'r':
                return $this->_rgb["R"];
            case 'green':
            case 'g':
                return $this->_rgb["G"];
            case 'blue':
            case 'b':
                return $this->_rgb["B"];
            case 'hue':
            case 'h':
                return $this->_hsl["H"];
            case 'saturation':
            case 's':
                return $this->_hsl["S"];
            case 'lightness':
            case 'l':
                return $this->_hsl["L"];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
            E_USER_NOTICE
        );
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws Exception
     */
    public function __set(string $name, $value)
    {
        switch (strtolower($name)) {
            case 'red':
            case 'r':
                $this->_rgb["R"] = $value;
                $this->_hex = self::rgbToHex($this->_rgb);
                $this->_hsl = self::hexToHsl($this->_hex);
                break;
            case 'green':
            case 'g':
                $this->_rgb["G"] = $value;
                $this->_hex = self::rgbToHex($this->_rgb);
                $this->_hsl = self::hexToHsl($this->_hex);
                break;
            case 'blue':
            case 'b':
                $this->_rgb["B"] = $value;
                $this->_hex = self::rgbToHex($this->_rgb);
                $this->_hsl = self::hexToHsl($this->_hex);
                break;
            case 'hue':
            case 'h':
                $this->_hsl["H"] = $value;
                $this->_hex = self::hslToHex($this->_hsl);
                $this->_rgb = self::hexToRgb($this->_hex);
                break;
            case 'saturation':
            case 's':
                $this->_hsl["S"] = $value;
                $this->_hex = self::hslToHex($this->_hsl);
                $this->_rgb = self::hexToRgb($this->_hex);
                break;
            case 'lightness':
            case 'light':
            case 'l':
                $this->_hsl["L"] = $value;
                $this->_hex = self::hslToHex($this->_hsl);
                $this->_rgb = self::hexToRgb($this->_hex);
                break;
        }
    }
}
