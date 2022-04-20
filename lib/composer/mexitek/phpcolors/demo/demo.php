<!doctype html>
<html lang="en">
<head>
    <title>phpColors Demo</title>
    <?php

    require_once __DIR__ . '/../src/Mexitek/PHPColors/Color.php';

    use Mexitek\PHPColors\Color;

    // Use different colors to test
    $myBlue = new Color("#336699");
    $myBlack = new Color("#333");
    $myPurple = new Color("#913399");
    $myVintage = new Color("#bada55");

    // ************** No Need to Change Below **********************
    ?>
    <style>
        .block {
            height: 100px;
            width: 200px;
            font-size: 20px;
            text-align: center;
            padding-top: 100px;
            display: block;
            margin: 0;
            float: left;
        }

        .wide-block {
            width: 360px;
            padding-top: 70px;
            padding-left: 20px;
            padding-right: 20px;
            margin-top: 10px;
        }

        .clear {
            clear: both;
        }

        .testDiv {
            <?= $myBlue->getCssGradient()?>
            color: <?=($myBlue->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv.plain {
            background: #<?= $myBlue->getHex()?>;
            color: <?=($myBlue->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv2 {
            <?= $myBlack->getCssGradient()?>
            color: <?=($myBlack->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv2.plain {
            background: #<?= $myBlack->getHex();?>;
            color: <?=($myBlack->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv3 {
            <?= $myPurple->getCssGradient()?>
            color: <?=($myPurple->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv3.plain {
            background: #<?= $myPurple->getHex()?>;
            color: <?=($myPurple->isDark() ? "#EEE":"#333")?>;
        }

        .testDiv4 {
            <?= $myVintage->getCssGradient(30, true)?>
            color: <?=($myVintage->isDark() ? "#EEE":"#333")?>;
        }
    </style>
</head>
<body>
<div class="clear"></div>
<div class="block testDiv">phpColor Gradient #<?= $myBlue->getHex() ?></div>
<div class="block testDiv plain">Plain #<?= $myBlue->getHex() ?></div>
<div class="clear"></div>
<div class="block testDiv2">phpColor Gradient #<?= $myBlack->getHex() ?></div>
<div class="block testDiv2 plain">Plain #<?= $myBlack->getHex() ?></div>
<div class="clear"></div>
<div class="block testDiv3">phpColor Gradient #<?= $myPurple->getHex() ?></div>
<div class="block testDiv3 plain">Plain #<?= $myPurple->getHex() ?></div>
<div class="clear"></div>
<div class="block wide-block testDiv4">
    phpColor Gradient with vintage browsers support #<?= $myVintage->getHex() ?>
</div>
</body>
</html>
