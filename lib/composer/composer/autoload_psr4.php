<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname(dirname($vendorDir));

return array(
    'OC\\Core\\' => array($baseDir . '/core'),
    'OC\\' => array($baseDir . '/lib/private'),
    'OCP\\' => array($baseDir . '/lib/public'),
    'NCU\\' => array($baseDir . '/lib/unstable'),
    'Bamarni\\Composer\\Bin\\' => array($vendorDir . '/bamarni/composer-bin-plugin/src'),
    '' => array($baseDir . '/lib/private/legacy'),
);
