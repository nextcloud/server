<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;

// Test that a hex starting with '#' is supported as input
$expected = array(
    "#ffffff",
    "#00ff00",
    "#000000",
    "#ff9a00",
);

foreach ($expected as $input) {
    $color = new Color($input);
    Assert::same((string) $color, $input, 'Incorrect color returned.');
}
