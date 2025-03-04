<?php

use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\LDAP;
use OCA\User_LDAP\Wizard;
use OCP\Server;
use OCP\Util;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$l = Util::getL10N('user_ldap');

if (!isset($_POST['action'])) {
	\OC_JSON::error(['message' => $l->t('No action specified')]);
}
$action = (string)$_POST['action'];

if (!isset($_POST['ldap_serverconfig_chooser'])) {
	\OC_JSON::error(['message' => $l->t('No configuration specified')]);
}
$prefix = (string)$_POST['ldap_serverconfig_chooser'];

$ldapWrapper = new LDAP();
$configuration = new Configuration($prefix);

$con = new \OCA\User_LDAP\Connection($ldapWrapper, $prefix, null);
$con->setConfiguration($configuration->getConfiguration());
$con->ldapConfigurationActive = (string)true;
$con->setIgnoreValidation(true);

$factory = Server::get(AccessFactory::class);
$access = $factory->get($con);

$wizard = new Wizard($configuration, $ldapWrapper, $access);

switch ($action) {
	case 'guessPortAndTLS':
	case 'guessBaseDN':
	case 'detectEmailAttribute':
	case 'detectUserDisplayNameAttribute':
	case 'determineGroupMemberAssoc':
	case 'determineUserObjectClasses':
	case 'determineGroupObjectClasses':
	case 'determineGroupsForUsers':
	case 'determineGroupsForGroups':
	case 'determineAttributes':
	case 'getUserListFilter':
	case 'getUserLoginFilter':
	case 'getGroupFilter':
	case 'countUsers':
	case 'countGroups':
	case 'countInBaseDN':
		try {
			$result = $wizard->$action();
			if ($result !== false) {
				\OC_JSON::success($result->getResultArray());
				exit;
			}
		} catch (\Exception $e) {
			\OC_JSON::error(['message' => $e->getMessage(), 'code' => $e->getCode()]);
			exit;
		}
		\OC_JSON::error();
		exit;
		break;

	case 'testLoginName': {
		try {
			$loginName = $_POST['ldap_test_loginname'];
			$result = $wizard->$action($loginName);
			if ($result !== false) {
				\OC_JSON::success($result->getResultArray());
				exit;
			}
		} catch (\Exception $e) {
			\OC_JSON::error(['message' => $e->getMessage()]);
			exit;
		}
		\OC_JSON::error();
		exit;
		break;
	}

	case 'save':
		$key = $_POST['cfgkey'] ?? false;
		$val = $_POST['cfgval'] ?? null;
		if ($key === false || is_null($val)) {
			\OC_JSON::error(['message' => $l->t('No data specified')]);
			exit;
		}
		if (is_array($key)) {
			\OC_JSON::error(['message' => $l->t('Invalid data specified')]);
			exit;
		}
		$cfg = [$key => $val];
		$setParameters = [];
		$configuration->setConfiguration($cfg, $setParameters);
		if (!in_array($key, $setParameters)) {
			\OC_JSON::error(['message' => $l->t('Could not set configuration %1$s to %2$s', [$key, $setParameters[0]])]);
			exit;
		}
		$configuration->saveConfiguration();
		//clear the cache on save
		$connection = new \OCA\User_LDAP\Connection($ldapWrapper, $prefix);
		$connection->clearCache();
		\OC_JSON::success();
		break;
	default:
		\OC_JSON::error(['message' => $l->t('Action does not exist')]);
		break;
}
