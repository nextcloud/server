<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;


$expected = array(
    "ffffff" => array("ff0000", "ff7f7f"), // ffffff + ff0000 = ff7f7f
    "00ff00" => array("ff0000", "7f7f00"),
    "000000" => array("ff0000", "7f0000"),
    "002fff" => array("000000", "00177f"),
    "00ffed" => array("000000", "007f76"),
    "ff9a00" => array("000000", "7f4d00"),
    "ff9a00" => array("ffffff", "ffcc7f"),
    "00ff2d" => array("ffffff", "7fff96"),
    "8D43B4" => array("35CF64", "61898c"),
);

foreach ($expected as $original => $complementary) {
    $color = new Color($original);
    Assert::same($complementary[1], $color->mix($complementary[0]), 'Incorrect mix color returned.');
}
