<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

if (!isset($argv[1])) {
	echo 'Clover file is missing' . PHP_EOL;
	exit;
}

try {
	$cloverFile = $argv[1];

	$doc = simplexml_load_file($cloverFile);

	$metrics = [];
	foreach ($doc->project->metrics->attributes() as $k => $v) {
		$metrics[$k] = $v->__toString();
	}

	$c0 = $metrics['coveredmethods'] / $metrics['methods'];
	$c1 = $metrics['coveredelements'] / $metrics['elements'];
	$c2 = $metrics['coveredstatements'] / $metrics['statements'];

	echo $c0 . PHP_EOL;
	echo $c1 . PHP_EOL;
	echo $c2 . PHP_EOL;

	$percent = (int)($c2 * 100);
	$color = 'red';
	if ($percent >= 50) {
		$color = 'yellow';
	}
	if ($percent >= 75) {
		$color = 'green';
	}
	$content = file_get_contents("https://img.shields.io/badge/coverage-$percent%-$color.svg");
	file_put_contents('coverage.svg', $content);
} catch (Exception $ex) {
	echo $ex->getMessage() . PHP_EOL;
	$content = file_get_contents('https://img.shields.io/badge/coverage-ERROR-red.svg');
	file_put_contents('coverage.svg', $content);
}
