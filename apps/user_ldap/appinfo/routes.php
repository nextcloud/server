<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
$this->create('user_ldap_ajax_clearMappings', 'apps/user_ldap/ajax/clearMappings.php')
	->actionInclude('user_ldap/ajax/clearMappings.php');
$this->create('user_ldap_ajax_deleteConfiguration', 'apps/user_ldap/ajax/deleteConfiguration.php')
	->actionInclude('user_ldap/ajax/deleteConfiguration.php');
$this->create('user_ldap_ajax_getConfiguration', 'apps/user_ldap/ajax/getConfiguration.php')
	->actionInclude('user_ldap/ajax/getConfiguration.php');
$this->create('user_ldap_ajax_getNewServerConfigPrefix', 'apps/user_ldap/ajax/getNewServerConfigPrefix.php')
	->actionInclude('user_ldap/ajax/getNewServerConfigPrefix.php');
$this->create('user_ldap_ajax_setConfiguration', 'apps/user_ldap/ajax/setConfiguration.php')
	->actionInclude('user_ldap/ajax/setConfiguration.php');
$this->create('user_ldap_ajax_testConfiguration', 'apps/user_ldap/ajax/testConfiguration.php')
	->actionInclude('user_ldap/ajax/testConfiguration.php');
$this->create('user_ldap_ajax_wizard', 'apps/user_ldap/ajax/wizard.php')
	->actionInclude('user_ldap/ajax/wizard.php');

return [
	'routes' => [
		['name' => 'renewPassword#tryRenewPassword', 'url' => '/renewpassword', 'verb' => 'POST'],
		['name' => 'renewPassword#showRenewPasswordForm', 'url' => '/renewpassword/{user}', 'verb' => 'GET'],
		['name' => 'renewPassword#cancel', 'url' => '/renewpassword/cancel', 'verb' => 'GET'],
		['name' => 'renewPassword#showLoginFormInvalidPassword', 'url' => '/renewpassword/invalidlogin/{user}', 'verb' => 'GET'],
	],
];
