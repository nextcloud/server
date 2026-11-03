<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Derives larger fixtures from the captured Files page to model instances
 * with many apps (`large`) and pages that ship big lists (`xlarge`).
 *
 * Usage: php synthesize.php
 */

require __DIR__ . '/variants.php';

mt_srand(42);

function word(): string {
	static $syllables = ['ne', 'xt', 'clo', 'ud', 'fi', 'le', 'sha', 're', 'tä', 'lk', 'ça', 'ñu', 'ро', 'ми', '云', '端'];
	$word = '';
	for ($i = mt_rand(2, 5); $i > 0; $i--) {
		$word .= $syllables[mt_rand(0, count($syllables) - 1)];
	}
	return $word;
}

function entity(int $id): array {
	return [
		'id' => 'id-' . $id,
		'displayName' => word() . ' ' . word(),
		'enabled' => mt_rand(0, 1) === 1,
		'quota' => mt_rand(0, 1 << 30),
		'groups' => [word(), word()],
		'url' => '/index.php/apps/' . word() . '/' . $id . '?a=1&b=<' . word() . '>',
	];
}

$files = bench_load_fixture(__DIR__ . '/fixtures/files.json');

$capabilities = json_decode($files['core-capabilities'], true);
for ($i = 0; $i < 40; $i++) {
	$capabilities[word() . $i] = ['enabled' => true, 'version' => '1.' . $i, 'features' => array_map(fn () => word(), range(1, 20)), 'limits' => array_map(fn () => mt_rand(), range(1, 10))];
}

$large = $files;
$large['core-capabilities'] = json_encode($capabilities);
for ($i = 0; $i < 100; $i++) {
	$large['app' . $i . '-' . word()] = json_encode(match ($i % 4) {
		0 => mt_rand(0, 1) === 1,
		1 => mt_rand(),
		2 => word(),
		3 => ['enabled' => true, 'name' => word(), 'items' => array_map(fn () => word(), range(1, 8))],
	});
}
for ($i = 0; $i < 10; $i++) {
	$large['medium' . $i . '-list'] = json_encode(array_map('entity', range(1, 12)));
}

$xlarge = $large;
$xlarge['settings-groups'] = json_encode(array_map('entity', range(1, 2000)));
$xlarge['settings-users'] = json_encode(array_map('entity', range(1, 2000)));

foreach (['large' => $large, 'xlarge' => $xlarge] as $name => $states) {
	file_put_contents(__DIR__ . "/fixtures/$name.json", json_encode($states, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
	printf("%s: %d keys, %d bytes of JSON\n", $name, count($states), array_sum(array_map('strlen', $states)));
}
