<?php

$groupValue = '[{ "gid": "id1", "displayName": "group1" }]';

$groups = json_decode($groupValue ?? '');
// support values like group1,group2
if (is_string($groups)) {
	$groups = explode(',', $groups);
	// remove surrounding spaces in each group
	$groups = array_map('trim', $groups);
	// remove empty strings
	$groups = array_filter($groups);
}

print_r($groups);

foreach ($groups as $k => $v) {
	if (is_object($v)) {
		// Handle array of objects, e.g. [{gid: "1", displayName: "group1"}, ...]
		if (empty($v->gid) && $v->gid !== '0' && $v->gid !== 0) {
			continue;
		}
		$group = $v;
	} elseif (is_string($v)) {
		// Handle array of strings, e.g. ["group1", "group2", ...]
		$group = (object)['gid' => $v, 'displayName' => $v];
	} else {
		continue;
	}
	print_r($group);
}
