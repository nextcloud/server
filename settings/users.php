<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Daniel Molkentin <daniel@molkentin.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stephan Peijnik <speijnik@anexia-it.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

OC_Util::checkSubAdminUser();

OC_App::setActiveNavigationEntry( 'core_users' );

$userManager = \OC_User::getManager();
$groupManager = \OC_Group::getManager();

// Set the sort option: SORT_USERCOUNT or SORT_GROUPNAME
$sortGroupsBy = \OC\Group\MetaData::SORT_USERCOUNT;

if (\OC_App::isEnabled('user_ldap')) {
	$isLDAPUsed =
		   $groupManager->isBackendUsed('\OCA\user_ldap\GROUP_LDAP')
		|| $groupManager->isBackendUsed('\OCA\user_ldap\Group_Proxy');
	if ($isLDAPUsed) {
		// LDAP user count can be slow, so we sort by group name here
		$sortGroupsBy = \OC\Group\MetaData::SORT_GROUPNAME;
	}
}

$config = \OC::$server->getConfig();

$isAdmin = OC_User::isAdminUser(OC_User::getUser());

$groupsInfo = new \OC\Group\MetaData(OC_User::getUser(), $isAdmin, $groupManager);
$groupsInfo->setSorting($sortGroupsBy);
list($adminGroup, $groups) = $groupsInfo->get();

$recoveryAdminEnabled = OC_App::isEnabled('encryption') &&
					    $config->getAppValue( 'encryption', 'recoveryAdminEnabled', null );

if($isAdmin) {
	$subadmins = OC_SubAdmin::getAllSubAdmins();
}else{
	/* Retrieve group IDs from $groups array, so we can pass that information into OC_Group::displayNamesInGroups() */
	$gids = array();
	foreach($groups as $group) {
		if (isset($group['id'])) {
			$gids[] = $group['id'];
		}
	}
	$subadmins = false;
}

// load preset quotas
$quotaPreset=$config->getAppValue('files', 'quota_preset', '1 GB, 5 GB, 10 GB');
$quotaPreset=explode(',', $quotaPreset);
foreach($quotaPreset as &$preset) {
	$preset=trim($preset);
}
$quotaPreset=array_diff($quotaPreset, array('default', 'none'));

$defaultQuota=$config->getAppValue('files', 'default_quota', 'none');
$defaultQuotaIsUserDefined=array_search($defaultQuota, $quotaPreset)===false
	&& array_search($defaultQuota, array('none', 'default'))===false;

$tmpl = new OC_Template("settings", "users/main", "user");
$tmpl->assign('groups', $groups);
$tmpl->assign('sortGroups', $sortGroupsBy);
$tmpl->assign('adminGroup', $adminGroup);
$tmpl->assign('isAdmin', (int)$isAdmin);
$tmpl->assign('subadmins', $subadmins);
$tmpl->assign('numofgroups', count($groups) + count($adminGroup));
$tmpl->assign('quota_preset', $quotaPreset);
$tmpl->assign('default_quota', $defaultQuota);
$tmpl->assign('defaultQuotaIsUserDefined', $defaultQuotaIsUserDefined);
$tmpl->assign('recoveryAdminEnabled', $recoveryAdminEnabled);
$tmpl->assign('enableAvatars', \OC::$server->getConfig()->getSystemValue('enable_avatars', true) === true);

$tmpl->assign('show_storage_location', $config->getAppValue('core', 'umgmt_show_storage_location', 'false'));
$tmpl->assign('show_last_login', $config->getAppValue('core', 'umgmt_show_last_login', 'false'));
$tmpl->assign('show_email', $config->getAppValue('core', 'umgmt_show_email', 'false'));
$tmpl->assign('show_backend', $config->getAppValue('core', 'umgmt_show_backend', 'false'));
$tmpl->assign('send_email', $config->getAppValue('core', 'umgmt_send_email', 'false'));

$tmpl->printPage();
