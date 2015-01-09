<?php

$configInstance = \OC::$server->getConfig();

//detect if we can switch on naming guidelines. We won't do it on conflicts.
//it's a bit spaghetti, but hey.
$state = $configInstance->getSystemValue('ldapIgnoreNamingRules', 'unset');
if($state === 'unset') {
	$configInstance->setSystemValue('ldapIgnoreNamingRules', false);
}

$installedVersion = $configInstance->getAppValue('user_ldap', 'installed_version');
$enableRawMode = version_compare($installedVersion, '0.4.1', '<');

$helper = new \OCA\user_ldap\lib\Helper();
$configPrefixes = $helper->getServerConfigurationPrefixes(true);
$ldap = new OCA\user_ldap\lib\LDAP();
foreach($configPrefixes as $config) {
	$connection = new OCA\user_ldap\lib\Connection($ldap, $config);

	$state = $configInstance->getAppValue(
		'user_ldap', $config.'ldap_uuid_user_attribute', 'not existing');
	if($state === 'non existing') {
		$value = $configInstance->getAppValue(
			'user_ldap', $config.'ldap_uuid_attribute', '');
		$configInstance->setAppValue(
			'user_ldap', $config.'ldap_uuid_user_attribute', $value);
		$configInstance->setAppValue(
			'user_ldap', $config.'ldap_uuid_group_attribute', $value);
	}

	$state = $configInstance->getAppValue(
		'user_ldap', $config.'ldap_expert_uuid_user_attr', 'not existing');
	if($state === 'non existing') {
		$value = $configInstance->getAppValue(
			'user_ldap', $config.'ldap_expert_uuid_attr', '');
		$configInstance->setAppValue(
			'user_ldap', $config.'ldap_expert_uuid_user_attr', $value);
		$configInstance->setAppValue(
			'user_ldap', $config.'ldap_expert_uuid_group_attr', $value);
	}

	if($enableRawMode) {
		$configInstance->setAppValue('user_ldap', $config.'ldap_user_filter_mode', 1);
		$configInstance->setAppValue('user_ldap', $config.'ldap_login_filter_mode', 1);
		$configInstance->setAppValue('user_ldap', $config.'ldap_group_filter_mode', 1);
	}
}
