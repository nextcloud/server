<?php

use Sami\Sami;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->notName('Api.php')
    ->notName('Params.php')
    ->in($dir = dirname(dirname(__DIR__)) . '/src')
;

return new Sami($iterator, [
    'title'         => 'Test',
    'theme'         => 'new_theme',
    'build_dir'     => __DIR__ . '/build',
    'cache_dir'     => __DIR__ . '/cache',
    'template_dirs' => [
        __DIR__ . '/template'
    ],
    'default_opened_level' => 1,
]);
