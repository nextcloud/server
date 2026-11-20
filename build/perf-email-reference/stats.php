<?php
// usage: php stats.php dir ; per version and request index: median/min/max summed DB ms and the email lookup query ms
$rows = [];
foreach (glob("$argv[1]/*.json") as $f) {
	[$v] = explode('-', basename($f));
	foreach (json_decode(file_get_contents($f), true) as $i => $p) {
		$db = 0; $look = 0;
		foreach ($p['collectors']['db']['queries'] as $q) {
			$ms = $q['executionMS'] * 1000; $db += $ms;
			if (str_contains($q['sql'], '"configkey" = :dcValue2) AND') && str_contains($q['sql'], 'SELECT "userid" FROM "oc_preferences"')) $look += $ms;
		}
		$rows[$i][$v]['db'][] = $db; $rows[$i][$v]['look'][] = $look;
	}
}
$med = function ($a) { sort($a); return sprintf('%6.2f [%5.2f..%6.2f]', $a[intdiv(count($a), 2)], min($a), max($a)); };
foreach ($rows as $i => $vs) foreach ($vs as $v => $d) printf("req%d %-8s n=%d db %s  lookup %s\n", $i + 1, $v, count($d['db']), $med($d['db']), $med($d['look']));
