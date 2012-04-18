<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../lib/base.php');
OC_Util::checkAdminUser();

// We have some javascript foo!
OC_Util::addScript( 'settings', 'users' );
OC_Util::addScript( 'core', 'multiselect' );
OC_Util::addStyle( 'settings', 'settings' );
OC_App::setActiveNavigationEntry( 'core_users' );

$users = array();
$groups = array();

foreach( OC_User::getUsers() as $i ){
	$users[] = array( "name" => $i, "groups" => join( ", ", OC_Group::getUserGroups( $i ) ),'quota'=>OC_Preferences::getValue($i,'files','quota','default'));
}

foreach( OC_Group::getGroups() as $i ){
	// Do some more work here soon
	$groups[] = array( "name" => $i );
}
$quotaPreset=OC_Appconfig::getValue('files','quota_preset','default,none,1 GB, 5 GB, 10 GB');
$quotaPreset=explode(',',$quotaPreset);
foreach($quotaPreset as &$preset){
	$preset=trim($preset);
}

$defaultQuota=OC_Appconfig::getValue('files','default_quota','none');

$tmpl = new OC_Template( "settings", "users", "user" );
$tmpl->assign( "users", $users );
$tmpl->assign( "groups", $groups );
$tmpl->assign( 'quota_preset', $quotaPreset);
$tmpl->assign( 'default_quota', $defaultQuota);
$tmpl->printPage();

?>

