<?php

if(php_sapi_name() !== 'cli') {
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

$ouName = 'Users';
$ouDN = 'ou=' . $ouName . ',' . $bdn;

//creates on OU
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

$users = ['alice', 'boris'];

foreach ($users as $uid) {
	$newDN = 'uid=' . $uid . ',' . $ouDN;
	$fn = ucfirst($uid);
	$sn = ucfirst(str_shuffle($uid));   // not so explicit but it's OK.

	$entry = [];
	$entry['cn'] = $fn . ' ' . $sn;
	$entry['objectclass'][] = 'inetOrgPerson';
	$entry['objectclass'][] = 'person';
	$entry['sn'] = $sn;
	$entry['userPassword'] = $uid;
	$entry['displayName'] = $sn . ', ' . $fn;

	$ok = ldap_add($cr, $newDN, $entry);
	if ($ok) {
		echo('created user ' . ': ' . $entry['cn'] . PHP_EOL);
	} else {
		die(ldap_error($cr));
	}
}
