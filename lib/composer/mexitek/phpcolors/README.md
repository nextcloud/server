# PHPColors [![Build Status](https://travis-ci.org/mexitek/phpColors.svg?branch=master)](https://travis-ci.org/mexitek/phpColors)

> A series of methods that let you manipulate colors. Just incase you ever need different shades of one color on the fly.

## Requirements

PHPColors requires PHP version 7.2.0 or greater.

## Installation

### Composer

Simply add `mexitek/phpcolors` to `composer.json` using `dev-master`.

```
composer require mexitek/phpcolors:dev-master
```

## How it works
Instantiate an object of the color class with a hex color string `$foo = new Color("336699")`.  That's it!  Now, call the methods you need for different color variants.

## Available Methods
- <strong>darken( [$amount] )</strong> : Allows you to obtain a darker shade of your color. Optionally you can decide to darken using a desired percentage.
- <strong>lighten( [$amount] )</strong> : Allows you to obtain a lighter shade of your color. Optionally you can decide to lighten using a desired percentage.
- <strong>mix($hex, [$amount] )</strong> : Allows you to mix another color to your color. Optionally you can decide to set the percent of second color or original color amount is ranged -100...0...100.
- <strong>isLight( [$hex] )</strong> : Determins whether your color (or the provide param) is considered a "light" color. Returns `TRUE` if color is light.
- <strong>isDark( [$hex] )</strong> : Determins whether your color (or the provide param) is considered a "dark" color. Returns `TRUE` if color is dark.
- <strong>makeGradient( [$amount] )</strong> : Returns an array with 2 indices `light` and `dark`, the initial color will either be selected for `light` or `dark` depending on its brightness, then the other color will be generated.  The optional param allows for a static lighten or darkened amount.
- <strong>complementary()</strong> : Returns the color "opposite" or complementary to your color.
- <strong>getHex()</strong> : Returns the original hex color.
- <strong>getHsl()</strong> : Returns HSL array for your color.
- <strong>getRgb()</strong> : Returns RGB array for your color.

> Auto lightens/darkens by 10% for sexily-subtle gradients

```php
/**
 * Using The Class
 */

use Mexitek\PHPColors\Color;

// Initialize my color
$myBlue = new Color("#336699");

echo $myBlue->darken();
// 1a334d

echo $myBlue->lighten();
// 8cb3d9

echo $myBlue->isLight();
// false

echo $myBlue->isDark();
// true

echo $myBlue->complementary();
// 996633

echo $myBlue->getHex();
// 336699

print_r( $myBlue->getHsl() );
// array( "H"=> 210, "S"=> 0.5, "L"=>0.4 );

print_r( $myBlue->getRgb() );
// array( "R"=> 51, "G"=> 102, "B"=>153 );

print_r($myBlue->makeGradient());
// array( "light"=>"8cb3d9" ,"dark"=>"336699" )

```


## Static Methods
- <strong>hslToHex( $hsl )</strong> : Convert a HSL array to a HEX string.
- <strong>hexToHsl( $hex )</strong> : Convert a HEX string into an HSL array.
- <strong>hexToRgb( $hex )</strong> : Convert a HEX string into an RGB array.
- <strong>rgbToHex( $rgb )</strong> : Convert an RGB array into a HEX string.

```php
/**
 * On The Fly Custom Calculations
 */

use Mexitek\PHPColors\Color;

 // Convert my HEX
 $myBlue = Color::hexToHsl("#336699");

 // Get crazy with the HUE
 $myBlue["H"] = 295;

 // Gimme my new color!!
 echo Color::hslToHex($myBlue);
 // 913399

```

## CSS Helpers
- <strong>getCssGradient( [$amount] [, $vintageBrowsers] )</strong> : Generates the CSS3 gradients for safari, chrome, opera, firefox and IE10. Optional percentage amount for lighter/darker shade. Optional boolean for older gradient CSS support.

> Would like to add support to custom gradient stops

```php

use Mexitek\PHPColors\Color;

// Initialize my color
$myBlue = new Color("#336699");

// Get CSS
echo $myBlue->getCssGradient();
/* - Actual output doesn't have comments and is single line

  // fallback background
  background: #336699;

  // IE Browsers
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#8cb3d9', endColorstr='#336699');

  // Safari 5.1+, Mobile Safari, Chrome 10+
  background-image: -webkit-linear-gradient(top, #8cb3d9, #336699);

  // Standards
  background-image: linear-gradient(to bottom, #8cb3d9, #336699);

*/

```

However, if you want to support the ancient browsers (which has negligible market share and almost died out), you can set the second parameter to `TRUE`. This will output:

```php

use Mexitek\PHPColors\Color;
$myBlue = new Color("#336699");

// Get CSS
echo $myBlue->getCssGradient(10, TRUE);
/* - Actual output doesn't have comments and is single line

  background: #336699; // fallback background
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#8cb3d9', endColorstr='#336699'); // IE Browsers
  background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#8cb3d9), to(#336699)); // Safari 4+, Chrome 1-9
  background-image: -webkit-linear-gradient(top, #8cb3d9, #336699); // Safari 5.1+, Mobile Safari, Chrome 10+
  background-image: -moz-linear-gradient(top, #8cb3d9, #336699); // Firefox 3.6+
  background-image: -o-linear-gradient(top, #8cb3d9, #336699); // Opera 11.10+
  background-image: linear-gradient(to bottom, #8cb3d9, #336699); // Standards

*/

```

## Github Contributors
- mexitek
- danielpataki
- alexmglover
- intuxicated
- pborreli
- curtisgibby
- matthewpatterson
- there4
- alex-humphreys
- zaher
- primozcigler
- thedavidmeister
- tylercd100
- Braunson

# License
See LICENSE file or [arlo.mit-license.org](http://arlo.mit-license.org)
