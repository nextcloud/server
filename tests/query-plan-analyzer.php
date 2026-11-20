<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Find queries recorded by Test\QueryPlanRecorder that filter a large table
 * without an index. PostgreSQL only.
 *
 * Usage: TEST_LOG_QUERIES=queries.log phpunit ...
 *        php tests/query-plan-analyzer.php [queries.log] [baseline.json] [--update-baseline]
 *
 * Seq scans are disabled before each EXPLAIN, so the planner picks an index
 * whenever one can serve the query, even on the tiny tables of a test run.
 * Flags filters no index can serve, e.g. LOWER(indexed) on an index over
 * (appid, configkey, indexed). Fails on offenders missing from the baseline.
 */

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Server;

$logFile = $argv[1] ?? 'queries.log';
$baselineFile = $argv[2] ?? __DIR__ . '/data/query-plan-baseline.json';
$updateBaseline = in_array('--update-baseline', $argv, true);

if (!is_readable($logFile)) {
	fwrite(STDERR, "cannot read $logFile\n");
	exit(1);
}

require_once __DIR__ . '/../lib/base.php';
require_once __DIR__ . '/autoload.php';

$connection = Server::get(IDBConnection::class);
if ($connection->getDatabaseProvider() !== IDBConnection::PLATFORM_POSTGRES) {
	fwrite(STDERR, "query plans are only analyzed on PostgreSQL\n");
	exit(1);
}
$prefix = Server::get(IConfig::class)->getSystemValueString('dbtableprefix', 'oc_');
$inner = $connection->getInner();
$inner->executeStatement('SET enable_seqscan = off');

/** @var array<string, list<string>> $indexedColumns */
$indexedColumns = [];
$result = $inner->executeQuery(
	'SELECT DISTINCT c.relname, a.attname FROM pg_index i
	JOIN pg_class c ON c.oid = i.indrelid
	JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
	WHERE c.relname IN (?)',
	[array_map(static fn (string $table): string => $prefix . $table, \Test\QueryPlanRecorder::largeTables())],
	[\Doctrine\DBAL\ArrayParameterType::STRING],
);
foreach ($result->fetchAllAssociative() as $row) {
	$indexedColumns[substr($row['relname'], strlen($prefix))][] = $row['attname'];
}

$queries = [];
foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
	$query = json_decode($line, true);
	if (is_array($query)) {
		$queries[$query['sql'] . "\0" . $query['origin']] ??= $query;
	}
}

/** @var array<string, array{sql: string, origin: string, test: string}> $offenders keyed by "table | origin | normalized sql" */
$offenders = [];
$failed = 0;
foreach ($queries as $query) {
	[$params, $types] = unserialize(base64_decode($query['params']));
	try {
		$explain = $inner->executeQuery('EXPLAIN (FORMAT JSON) ' . $query['sql'], $params, $types)->fetchOne();
	} catch (\Throwable) {
		// e.g. a table a test created and dropped again
		$failed++;
		continue;
	}
	$plan = json_decode($explain, true)[0]['Plan'] ?? [];
	$normalized = trim(preg_replace('/\s+/', ' ', $query['sql']));
	foreach (array_unique(\Test\QueryPlanRecorder::unindexedFilters($plan, $prefix, $indexedColumns)) as $table) {
		$offenders["$table | {$query['origin']} | $normalized"] = $query;
	}
}
ksort($offenders);

printf("%d distinct queries, %d could not be explained, %d filter a large table without an index\n", count($queries), $failed, count($offenders));

if ($updateBaseline) {
	file_put_contents($baselineFile, json_encode(array_keys($offenders), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
	printf("Wrote %s\n", $baselineFile);
	exit(0);
}

$baseline = is_readable($baselineFile) ? json_decode(file_get_contents($baselineFile), true) : [];
$new = array_diff_key($offenders, array_flip($baseline));
$gone = array_diff($baseline, array_keys($offenders));

if ($gone !== []) {
	printf("\n%d baseline entries no longer occur, remove them from %s:\n", count($gone), $baselineFile);
	foreach ($gone as $key) {
		echo "  $key\n";
	}
}

if ($new === []) {
	exit(0);
}

printf("\n%d new queries filter a large table without an index:\n", count($new));
foreach ($new as $key => $query) {
	printf("  %s\n    from %s\n", $key, $query['test']);
}
echo "\nAdd or fix an index, or if the scan is intended, add the line to the baseline.\n";
exit(1);
