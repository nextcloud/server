<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Daniel Molkentin <daniel@molkentin.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stephan Peijnik <speijnik@anexia-it.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Pulzer <t.pulzer@kniel.de>
 *
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

\OC::$server->getNavigationManager()->setActiveEntry('core_users');

$userManager = \OC::$server->getUserManager();
$groupManager = \OC::$server->getGroupManager();
$appManager = \OC::$server->getAppManager();

// Set the sort option: SORT_USERCOUNT or SORT_GROUPNAME
$sortGroupsBy = \OC\Group\MetaData::SORT_USERCOUNT;

$config = \OC::$server->getConfig();

if ($config->getSystemValue('sort_groups_by_name', false)) {
	$sortGroupsBy = \OC\Group\MetaData::SORT_GROUPNAME;
} else {
	$isLDAPUsed = false;
	if ($appManager->isEnabledForUser('user_ldap')) {
		$isLDAPUsed =
			$groupManager->isBackendUsed('\OCA\User_LDAP\Group_LDAP')
			|| $groupManager->isBackendUsed('\OCA\User_LDAP\Group_Proxy');
		if ($isLDAPUsed) {
			// LDAP user count can be slow, so we sort by group name here
			$sortGroupsBy = \OC\Group\MetaData::SORT_GROUPNAME;
		}
	}
}

$isAdmin = OC_User::isAdminUser(OC_User::getUser());

$isDisabled = !OC_User::isEnabled(OC_User::getUser());

$groupsInfo = new \OC\Group\MetaData(
	OC_User::getUser(),
	$isAdmin,
	$groupManager,
	\OC::$server->getUserSession()
);

$groupsInfo->setSorting($sortGroupsBy);
list($adminGroup, $groups) = $groupsInfo->get();

$recoveryAdminEnabled = $appManager->isEnabledForUser('encryption') &&
					    $config->getAppValue( 'encryption', 'recoveryAdminEnabled', '0');

if($isAdmin) {
	$subAdmins = \OC::$server->getGroupManager()->getSubAdmin()->getAllSubAdmins();
	// New class returns IUser[] so convert back
	$result = [];
	foreach ($subAdmins as $subAdmin) {
		$result[] = [
			'gid' => $subAdmin['group']->getGID(),
			'uid' => $subAdmin['user']->getUID(),
		];
	}
	$subAdmins = $result;
}else{
	/* Retrieve group IDs from $groups array, so we can pass that information into OC_Group::displayNamesInGroups() */
	$gids = array();
	foreach($groups as $group) {
		if (isset($group['id'])) {
			$gids[] = $group['id'];
		}
	}
	$subAdmins = false;
}

$disabledUsers = $isLDAPUsed ? 0 : $userManager->countDisabledUsers();
$disabledUsersGroup = [
	'id' => '_disabledUsers',
	'name' => '_disabledUsers',
	'usercount' => $disabledUsers
];

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

\OC::$server->getEventDispatcher()->dispatch('OC\Settings\Users::loadAdditionalScripts');

$tmpl = new OC_Template("settings", "users/main", "user");
$tmpl->assign('groups', $groups);
$tmpl->assign('sortGroups', $sortGroupsBy);
$tmpl->assign('adminGroup', $adminGroup);
$tmpl->assign('disabledUsersGroup', $disabledUsersGroup);
$tmpl->assign('isAdmin', (int)$isAdmin);
$tmpl->assign('subadmins', $subAdmins);
$tmpl->assign('numofgroups', count($groups) + count($adminGroup));
$tmpl->assign('quota_preset', $quotaPreset);
$tmpl->assign('default_quota', $defaultQuota);
$tmpl->assign('defaultQuotaIsUserDefined', $defaultQuotaIsUserDefined);
$tmpl->assign('recoveryAdminEnabled', $recoveryAdminEnabled);

$tmpl->assign('show_storage_location', $config->getAppValue('core', 'umgmt_show_storage_location', 'false'));
$tmpl->assign('show_last_login', $config->getAppValue('core', 'umgmt_show_last_login', 'false'));
$tmpl->assign('show_email', $config->getAppValue('core', 'umgmt_show_email', 'false'));
$tmpl->assign('show_backend', $config->getAppValue('core', 'umgmt_show_backend', 'false'));
$tmpl->assign('send_email', $config->getAppValue('core', 'umgmt_send_email', 'false'));

$tmpl->printPage();
