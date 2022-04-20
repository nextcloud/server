<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;

// Colors in RGB, for testing.
$blue = [
    'R' => 0,
    'G' => 158,
    'B' => 204,
];
$yellow = [
    'R' => 244,
    'G' => 231,
    'B' => 15,
];
$black = [
    'R' => 0,
    'G' => 0,
    'B' => 0,
];
$white = [
    'R' => 255,
    'G' => 255,
    'B' => 255,
];

// Test cases.
$colorsToConvert = array(
    'blue' => [ // rgb(0, 158, 204)
        'hex' => '009ecc',
        'rgb' => $blue,
    ],
    'yellow' => [ // rgb(244, 231, 15)
        'hex' => 'f4e70f',
        'rgb' => $yellow,
    ],
    'black' => [
        'hex' => '000000',
        'rgb' => $black,
    ],
    'white' => [
        'hex' => 'ffffff',
        'rgb' => $white,
    ],
);


foreach ($colorsToConvert as $color) {
    $rgb = $color['rgb'];
    $hex = $color['hex'];

    $answer = Color::rgbToHex($rgb);
    Assert::same(
        $hex,
        $answer,
        'Incorrect hex result: "' . Color::rgbToString($rgb) .
        '" should convert to "' . $hex .
        '", but output was: "' . $answer . '".'
    );

    $revertAnswer = Color::hexToRgb($hex);
    Assert::same(
        $rgb,
        $revertAnswer,
        'Incorrect rgb result: "' . $hex .
        '" should convert to "' . Color::rgbToString($rgb) .
        '", but output was: "' . Color::rgbToString($revertAnswer) . '".'
    );
}
