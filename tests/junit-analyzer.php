<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Analyse a PHPUnit JUnit log: slowest tests, slowest classes, and whether the
 * suite degrades over execution order.
 *
 * Usage: php tests/junit-analyzer.php [junit.xml] [topN]
 *
 * The bucket table splits the run into equal chunks of execution order. A rising
 * median means the suite itself degrades (accumulated DB rows, leaked memory);
 * a flat median with rising sum/max means a few slow tests happen to run late.
 */

const BUCKETS = 10;

$file = $argv[1] ?? 'junit.xml';
$topCount = (int)($argv[2] ?? 30);

if (!is_readable($file)) {
	fwrite(STDERR, "cannot read $file\n");
	exit(1);
}

libxml_use_internal_errors(true);

$reader = new XMLReader();
if (!$reader->open($file)) {
	fwrite(STDERR, "cannot open $file\n");
	exit(1);
}

/** @var list<array{class: string, name: string, duration: float}> $tests in execution order */
$tests = [];
while ($reader->read()) {
	if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'testcase') {
		continue;
	}
	$tests[] = [
		'class' => $reader->getAttribute('class') ?: '(none)',
		'name' => (string)$reader->getAttribute('name'),
		'duration' => (float)$reader->getAttribute('time'),
	];
}
$reader->close();

$testCount = count($tests);
if ($testCount === 0) {
	$error = libxml_get_errors()[0] ?? null;
	fwrite(STDERR, "no testcase elements found in $file"
		. ($error !== null ? ': ' . trim($error->message) : '') . "\n");
	exit(1);
}

$totalDuration = array_sum(array_column($tests, 'duration'));
printf("%d tests, %.1fs total (%.1f min)\n\n", $testCount, $totalDuration, $totalDuration / 60);

if ($totalDuration <= 0) {
	fwrite(STDERR, "no timing data to rank\n");
	exit(0);
}

$chunks = array_chunk($tests, (int)ceil($testCount / BUCKETS));
$buckets = count($chunks);

printf("Execution order, %d buckets (are later tests slower?)\n", $buckets);
echo "  bucket        tests    sum(s)   mean(ms)   median(ms)    max(s)  cum%\n";

$durationSoFar = 0.0;
foreach ($chunks as $bucket => $chunk) {
	$durations = array_column($chunk, 'duration');
	sort($durations);
	$inBucket = count($durations);
	$bucketDuration = array_sum($durations);
	$durationSoFar += $bucketDuration;

	printf(
		"  %3d-%3d%%  %7d %9.1f %10.2f %12.2f %9.2f %5.1f%%\n",
		$bucket * 100 / $buckets,
		($bucket + 1) * 100 / $buckets,
		$inBucket,
		$bucketDuration,
		$bucketDuration / $inBucket * 1000,
		$durations[intdiv($inBucket, 2)] * 1000,
		max($durations),
		$durationSoFar / $totalDuration * 100,
	);
}

$slowestFirst = $tests;
usort($slowestFirst, static fn (array $a, array $b): int => $b['duration'] <=> $a['duration']);

printf("\nTop %d slowest tests\n", $topCount);
foreach (array_slice($slowestFirst, 0, $topCount) as $test) {
	printf("  %8.2fs  %s::%s\n", $test['duration'], $test['class'], $test['name']);
}

/** @var array<string, array{duration: float, tests: int}> $classes */
$classes = [];
foreach ($tests as $test) {
	$classes[$test['class']] ??= ['duration' => 0.0, 'tests' => 0];
	$classes[$test['class']]['duration'] += $test['duration'];
	$classes[$test['class']]['tests']++;
}
uasort($classes, static fn (array $a, array $b): int => $b['duration'] <=> $a['duration']);

printf("\nTop %d slowest classes (sum of its tests)\n", $topCount);
printf("  %9s %7s %10s  %s\n", 'sum', 'tests', 'mean(ms)', 'class');
foreach (array_slice($classes, 0, $topCount, true) as $class => $stats) {
	printf(
		"  %8.2fs %7d %10.2f  %s\n",
		$stats['duration'],
		$stats['tests'],
		$stats['duration'] / $stats['tests'] * 1000,
		$class,
	);
}

// How top-heavy is the run? A handful of tests dominating reads very differently
// from the cost being spread evenly.
$durationSoFar = 0.0;
$testsInHalfTheRuntime = 0;
foreach ($slowestFirst as $test) {
	$durationSoFar += $test['duration'];
	$testsInHalfTheRuntime++;
	if ($durationSoFar >= $totalDuration / 2) {
		break;
	}
}
printf(
	"\nThe slowest %d tests (%.1f%% of tests) account for 50%% of the runtime.\n",
	$testsInHalfTheRuntime,
	$testsInHalfTheRuntime / $testCount * 100,
);
