<?php

require __DIR__ . '/bootstrap.php';

use Mexitek\PHPColors\Color;
use Tester\Assert;


$expected = array(
    "336699" => "264d73",
    "913399" => "6d2673"
);

foreach ($expected as $original => $darker) {
    $color = new Color($original);
    Assert::same($darker, $color->darken(), 'Incorrect darker color returned.');
}


$expected = array(
    "336699" => "4080bf",
    "913399" => "b540bf"
);

foreach ($expected as $original => $lighter) {
    $color = new Color($original);
    Assert::same($lighter, $color->lighten(), "Incorrect lighter color returned.");
}
