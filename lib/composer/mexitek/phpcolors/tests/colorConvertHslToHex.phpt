<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;

// Colors in HSL, for testing.
$blue = [
    'H' => 194,
    'S' => 1.0,
    'L' => 0.4,
];
$yellow = [
    'H' => 57,
    'S' => 0.91,
    'L' => 0.51,
];
$black = [
    'H' => 0,
    'S' => 0.0,
    'L' => 0.0,
];
$grey = [
    'H' => 0,
    'S' => 0.0,
    'L' => 0.65,
];
$white = [
    'H' => 0,
    'S' => 0.0,
    'L' => 1.0,
];

// Test cases.
$colorsToConvert = array(
    'blue' => [ // hsl(194, 100%, 40%)
        'hex' => '009ccc',
        'hsl' => $blue,
    ],
    'yellow' => [ // hsl(57, 91%, 51%)
        'hex' => 'f4e810',
        'hsl' => $yellow,
    ],
    'black' => [
        'hex' => '000000',
        'hsl' => $black,
    ],
    'grey' => [
        'hex' => 'a6a6a6',
        'hsl' => $grey,
    ],
    'white' => [
        'hex' => 'ffffff',
        'hsl' => $white,
    ],
);


foreach ($colorsToConvert as $color) {
    $hsl = $color['hsl'];
    $hex = $color['hex'];

    $answer = Color::hslToHex($hsl);
    Assert::same(
        $hex,
        $answer,
        'Incorrect hex result: "' . json_encode($hsl) .
        '" should convert to "' . $hex .
        '", but output was: "' . $answer . '".'
    );
}
