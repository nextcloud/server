<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;


$isDark = array(
    "000000" => true,
    "336699" => true,
    "913399" => true,
    "E5C3E8" => false,
    "D7E8DD" => false,
    "218A47" => true,
    "3D41CA" => true,
    "E5CCDD" => false,
    "FFFFFF" => false,
);

foreach ($isDark as $colorHex => $state) {
    $color = new Color($colorHex);
    Assert::same($state, $color->isDark(), 'Incorrect dark color analyzed (#' . $colorHex . ').');
}

$isLight = array(
    "FFFFFF" => true,
    "A3FFE5" => true,
    "000000" => false,
);

foreach ($isLight as $colorHex => $state) {
    $color = new Color($colorHex);
    Assert::same($state, $color->isLight(), 'Incorrect light color analyzed (#' . $colorHex . ').');
}
