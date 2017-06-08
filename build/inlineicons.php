<?php

if ($argc < 2) {
	echo $argv[0] . ' <path to css file>' . PHP_EOL;
	return;
}

$sourceFile = $argv[1];

if (!is_file($sourceFile)) {
	echo $argv[1] . ' is not a file.' . PHP_EOL;
}

$srcFile = $argv[1];
$basePath = explode('/', $argv[1]);
array_pop($basePath);
$basePath = implode('/', $basePath);

$css = file_get_contents($srcFile);

$matches = [];
preg_match_all('/url\(.*\)/',$css, $matches);

foreach ($matches[0] as $match) {
	$path = substr($match, 5, -2);
	$path = explode('?', $path);

	$encoded = base64_encode(file_get_contents($basePath . '/' . $path[0]));
	$css = str_replace($match, 'url(\'data:image/svg+xml;base64,' . $encoded . '\')', $css);
}

echo $css;
