<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Rank the outgoing HTTP requests recorded by Test\HttpRequestLogger.
 *
 * Usage: TEST_LOG_HTTP=http-requests.log phpunit ...
 *        php tests/http-analyzer.php [http-requests.log] [topN]
 *
 * Tests should not reach the network: anything listed needs either a mocked
 * IClientService or a config value that prevents the request.
 */

$file = $argv[1] ?? 'http-requests.log';
$topCount = (int)($argv[2] ?? 20);

if (!is_readable($file)) {
	fwrite(STDERR, "cannot read $file\n");
	exit(1);
}

/** @var list<array{test: string, method: string, uri: string, outcome: string, duration: float}> $requests */
$requests = [];
foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
	$request = json_decode($line, true);
	if (is_array($request)) {
		$requests[] = $request;
	}
}

if ($requests === []) {
	echo "No outgoing HTTP requests were recorded.\n";
	exit(0);
}

$totalDuration = array_sum(array_column($requests, 'duration'));
printf("%d requests, %.1fs total\n", count($requests), $totalDuration);

/** @param callable(array): string $key */
function group(array $requests, callable $key): array {
	$groups = [];
	foreach ($requests as $request) {
		$name = $key($request);
		$groups[$name] ??= ['duration' => 0.0, 'requests' => 0];
		$groups[$name]['duration'] += $request['duration'];
		$groups[$name]['requests']++;
	}
	uasort($groups, static fn (array $a, array $b): int => $b['duration'] <=> $a['duration']);
	return $groups;
}

foreach ([
	'host' => static fn (array $r): string => parse_url($r['uri'], PHP_URL_HOST) ?: '(unparsed)',
	'test' => static fn (array $r): string => $r['test'],
] as $label => $key) {
	$groups = group($requests, $key);
	printf("\nRequests by %s\n", $label);
	printf("  %9s %9s  %s\n", 'sum', 'requests', $label);
	foreach (array_slice($groups, 0, $topCount, true) as $name => $stats) {
		printf("  %8.2fs %9d  %s\n", $stats['duration'], $stats['requests'], $name);
	}
}

usort($requests, static fn (array $a, array $b): int => $b['duration'] <=> $a['duration']);
printf("\nTop %d slowest requests\n", $topCount);
foreach (array_slice($requests, 0, $topCount) as $request) {
	printf(
		"  %8.2fs  %-6s %-4s %s\n            %s\n",
		$request['duration'],
		$request['method'],
		$request['outcome'],
		$request['uri'],
		$request['test'],
	);
}
