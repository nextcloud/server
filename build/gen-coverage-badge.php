<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

if (!isset($argv[1])) {
	echo "Clover file is missing" . PHP_EOL;
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
} catch(Exception $ex) {
	echo $ex->getMessage() . PHP_EOL;
	$content = file_get_contents("https://img.shields.io/badge/coverage-ERROR-red.svg");
	file_put_contents('coverage.svg', $content);
}
