<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Server side cost of rendering each initial state variant.
 *
 * Usage: php bench-php.php [iterations] [fixture ...]
 */

require __DIR__ . '/variants.php';

$iterations = (int)($argv[1] ?? 2000);
$fixtures = array_slice($argv, 2) ?: glob(__DIR__ . '/fixtures/*.json');
$nonce = base64_encode(random_bytes(32));

printf("PHP %s, %d iterations, median µs per render\n\n", PHP_VERSION, $iterations);
printf("| %-15s | %5s | %9s |", 'fixture', 'keys', 'JSON B');
foreach (VARIANTS as $variant) {
	printf(' %s |', $variant);
}
echo "\n|" . str_repeat('---|', count(VARIANTS) + 3) . "\n";

foreach ($fixtures as $fixture) {
	$states = bench_load_fixture($fixture);
	$row = [];
	foreach (VARIANTS as $variant) {
		$samples = [];
		for ($i = 0; $i < $iterations; $i++) {
			$start = hrtime(true);
			ob_start();
			bench_render($variant, $states, $nonce);
			ob_end_clean();
			$samples[] = hrtime(true) - $start;
		}
		sort($samples);
		$row[$variant] = $samples[intdiv(count($samples), 2)] / 1000;
	}
	printf('| %-15s | %5d | %9d |', basename($fixture, '.json'), count($states), array_sum(array_map('strlen', $states)));
	foreach (VARIANTS as $variant) {
		printf(' %.1f |', $row[$variant]);
	}
	echo "\n";
}
