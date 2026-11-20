<?php
// usage: php summ.php export.json ; one line per request: queries, summed DB ms, slowest query ms
foreach (json_decode(file_get_contents($argv[1]), true) as $p) {
	$q = $p['collectors']['db']['queries'] ?? [];
	$ms = array_map(fn ($x) => ($x['executionMS'] ?? 0) * 1000, $q);
	$max = $ms ? max($ms) : 0;
	$slow = $ms ? $q[array_search($max, $ms)]['sql'] : '';
	printf("%-6s %-45s q=%3d db=%7.2fms max=%6.2fms %s\n", $p['method'], substr($p['url'], 0, 45), count($q), array_sum($ms), $max, substr($slow, 0, 90));
}
