<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkSubAdminUser();

// We have some javascript foo!
OC_Util::addScript( 'settings', 'users' );
OC_Util::addScript( 'core', 'multiselect' );
OC_Util::addScript( 'core', 'singleselect' );
OC_Util::addScript('core', 'jquery.inview');
OC_Util::addStyle( 'settings', 'settings' );
OC_App::setActiveNavigationEntry( 'core_users' );

$users = array();
$groups = array();

if (isset($_GET['offset'])) {
	$offset = $_GET['offset'];
} else {
	$offset = 0;
}
if (isset($_GET['limit'])) {
	$limit = $_GET['limit'];
} else {
	$limit = 10;
}

$isadmin = OC_User::isAdminUser(OC_User::getUser());
$recoveryAdminEnabled = OC_App::isEnabled('files_encryption') &&
					    OC_Appconfig::getValue( 'files_encryption', 'recoveryAdminEnabled' );

if($isadmin) {
	$accessiblegroups = OC_Group::getGroups();
	$accessibleusers = OC_User::getDisplayNames('', 30);
	$subadmins = OC_SubAdmin::getAllSubAdmins();
}else{
	$accessiblegroups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$accessibleusers = OC_Group::displayNamesInGroups($accessiblegroups, '', 30);
	$subadmins = false;
}

// load preset quotas
$quotaPreset=OC_Appconfig::getValue('files', 'quota_preset', '1 GB, 5 GB, 10 GB');
$quotaPreset=explode(',', $quotaPreset);
foreach($quotaPreset as &$preset) {
	$preset=trim($preset);
}
$quotaPreset=array_diff($quotaPreset, array('default', 'none'));

$defaultQuota=OC_Appconfig::getValue('files', 'default_quota', 'none');
$defaultQuotaIsUserDefined=array_search($defaultQuota, $quotaPreset)===false
	&& array_search($defaultQuota, array('none', 'default'))===false;

// load users and quota
foreach($accessibleusers as $uid => $displayName) {
	$quota=OC_Preferences::getValue($uid, 'files', 'quota', 'default');
	$isQuotaUserDefined=array_search($quota, $quotaPreset)===false
		&& array_search($quota, array('none', 'default'))===false;

	$name = $displayName;
	if ( $displayName !== $uid ) {
		$name = $name . ' ('.$uid.')';
	}

	$users[] = array(
		"name" => $uid,
		"displayName" => $displayName,
		"groups" => OC_Group::getUserGroups($uid),
		'quota' => $quota,
		'isQuotaUserDefined' => $isQuotaUserDefined,
		'subadmin' => OC_SubAdmin::getSubAdminsGroups($uid),
	);
}

foreach( $accessiblegroups as $gid ) {
	if (!OC_User::isAdminUser($gid)) {
		$groups[] = array(
			'id' => str_replace(' ','', $gid ),
			'name' => $gid,
			'useringroup' => OC_Group::usersInGroup($gid, '', $limit, $offset)
		);
	} else {
		$adminGroup[] =  array(
			'id' => str_replace(' ','', $gid ),
			'name' => $gid,
			'useringroup' => OC_Group::usersInGroup($gid, '', $limit, $offset)
		);
	}
}

$tmpl = new OC_Template( "settings", "users", "user" );
$tmpl->assign( 'users', $users );
$tmpl->assign( 'groups', $groups );
$tmpl->assign( 'adminGroup', $adminGroup );
$tmpl->assign( 'isadmin', (int) $isadmin);
$tmpl->assign( 'subadmins', $subadmins);
$tmpl->assign( 'numofgroups', count($accessiblegroups));
$tmpl->assign( 'quota_preset', $quotaPreset);
$tmpl->assign( 'default_quota', $defaultQuota);
$tmpl->assign( 'defaultQuotaIsUserDefined', $defaultQuotaIsUserDefined);
$tmpl->assign( 'recoveryAdminEnabled', $recoveryAdminEnabled);
$tmpl->assign('enableAvatars', \OC_Config::getValue('enable_avatars', true));
$tmpl->printPage();
