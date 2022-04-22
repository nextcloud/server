<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;


$expected = array(
    "ff0000" => "00ffff",
    "0000ff" => "ffff00",
    "00ff00" => "ff00ff",
    "ffff00" => "0000ff",
    "00ffff" => "ff0000",
    "49cbaf" => "cb4965",
    "003eb2" => "b27400",
    "b27400" => "003eb2",
    "ffff99" => "9999ff",
    "ccff00" => "3300ff",
    "3300ff" => "ccff00",
    "fb4a2c" => "2cddfb",
    "9cebff" => "ffb09c",
);

foreach ($expected as $original => $complementary) {
    $color = new Color($original);
    Assert::same($complementary, $color->complementary(), 'Incorrect complementary color returned.');
}
