<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$l = \OC::$server->getL10N('user_ldap');

if (!isset($_POST['action'])) {
	\OC_JSON::error(['message' => $l->t('No action specified')]);
}
$action = (string)$_POST['action'];

if (!isset($_POST['ldap_serverconfig_chooser'])) {
	\OC_JSON::error(['message' => $l->t('No configuration specified')]);
}
$prefix = (string)$_POST['ldap_serverconfig_chooser'];

$ldapWrapper = new \OCA\User_LDAP\LDAP();
$configuration = new \OCA\User_LDAP\Configuration($prefix);

$con = new \OCA\User_LDAP\Connection($ldapWrapper, $prefix, null);
$con->setConfiguration($configuration->getConfiguration());
$con->ldapConfigurationActive = true;
$con->setIgnoreValidation(true);

$factory = \OC::$server->get(\OCA\User_LDAP\AccessFactory::class);
$access = $factory->get($con);

$wizard = new \OCA\User_LDAP\Wizard($configuration, $ldapWrapper, $access);

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
			\OC_JSON::error(['message' => $l->t($key.
				' Could not set configuration %s', $setParameters[0])]);
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
