<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
if (php_sapi_name() !== 'cli') {
	print('Only via CLI, please.');
	exit(1);
}

include __DIR__ . '/config.php';

$cr = ldap_connect($host, $port);
ldap_set_option($cr, LDAP_OPT_PROTOCOL_VERSION, 3);
$ok = ldap_bind($cr, $adn, $apwd);

if (!$ok) {
	die(ldap_error($cr));
}

$ouName = 'Groups';
$ouDN = 'ou=' . $ouName . ',' . $bdn;

//creates an OU
if (true) {
	$entry = [];
	$entry['objectclass'][] = 'top';
	$entry['objectclass'][] = 'organizationalunit';
	$entry['ou'] = $ouName;
	$b = ldap_add($cr, $ouDN, $entry);
	if (!$b) {
		die(ldap_error($cr));
	}
}

$groups = ['RedGroup', 'BlueGroup', 'GreenGroup', 'PurpleGroup'];
// groupOfNames requires groups to have at least one member
// the member used is created by createExplicitUsers.php script
$omniMember = 'uid=alice,ou=Users,' . $bdn;

foreach ($groups as $cn) {
	$newDN = 'cn=' . $cn . ',' . $ouDN;

	$entry = [];
	$entry['cn'] = $cn;
	$entry['objectclass'][] = 'groupOfNames';
	$entry['member'][] = $omniMember;

	$ok = ldap_add($cr, $newDN, $entry);
	if ($ok) {
		echo('created group ' . ': ' . $entry['cn'] . PHP_EOL);
	} else {
		die(ldap_error($cr));
	}
}
