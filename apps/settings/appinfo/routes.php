<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'resources' => [
		'AuthSettings' => ['url' => '/settings/personal/authtokens' , 'root' => ''],
	],
	'routes' => [
		['name' => 'AuthorizedGroup#saveSettings', 'url' => '/settings/authorizedgroups/saveSettings', 'verb' => 'POST'],

		['name' => 'AuthSettings#wipe', 'url' => '/settings/personal/authtokens/wipe/{id}', 'verb' => 'POST' , 'root' => ''],

		['name' => 'MailSettings#setMailSettings', 'url' => '/settings/admin/mailsettings', 'verb' => 'POST' , 'root' => ''],
		['name' => 'MailSettings#storeCredentials', 'url' => '/settings/admin/mailsettings/credentials', 'verb' => 'POST' , 'root' => ''],
		['name' => 'MailSettings#sendTestMail', 'url' => '/settings/admin/mailtest', 'verb' => 'POST' , 'root' => ''],

		['name' => 'AppSettings#getAppDiscoverJSON', 'url' => '/settings/api/apps/discover', 'verb' => 'GET', 'root' => ''],
		['name' => 'AppSettings#getAppDiscoverMedia', 'url' => '/settings/api/apps/media', 'verb' => 'GET', 'root' => ''],
		['name' => 'AppSettings#listCategories', 'url' => '/settings/apps/categories', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#listApps', 'url' => '/settings/apps/list', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#enableApp', 'url' => '/settings/apps/enable/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#enableApp', 'url' => '/settings/apps/enable/{appId}', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#enableApps', 'url' => '/settings/apps/enable', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#disableApp', 'url' => '/settings/apps/disable/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#disableApps', 'url' => '/settings/apps/disable', 'verb' => 'POST' , 'root' => ''],
		['name' => 'AppSettings#updateApp', 'url' => '/settings/apps/update/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#uninstallApp', 'url' => '/settings/apps/uninstall/{appId}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps/{category}', 'verb' => 'GET', 'defaults' => ['category' => ''] , 'root' => ''],
		['name' => 'AppSettings#viewApps', 'url' => '/settings/apps/{category}/{id}', 'verb' => 'GET', 'defaults' => ['category' => '', 'id' => ''] , 'root' => ''],
		['name' => 'AppSettings#force', 'url' => '/settings/apps/force', 'verb' => 'POST' , 'root' => ''],

		['name' => 'Users#setDisplayName', 'url' => '/settings/users/{username}/displayName', 'verb' => 'POST' , 'root' => ''],
		['name' => 'Users#setEMailAddress', 'url' => '/settings/users/{id}/mailAddress', 'verb' => 'PUT' , 'root' => ''],
		['name' => 'Users#setUserSettings', 'url' => '/settings/users/{username}/settings', 'verb' => 'PUT' , 'root' => ''],
		['name' => 'Users#getVerificationCode', 'url' => '/settings/users/{account}/verify', 'verb' => 'GET' , 'root' => ''],
		['name' => 'Users#usersList', 'url' => '/settings/users', 'verb' => 'GET' , 'root' => ''],
		['name' => 'Users#usersListByGroup', 'url' => '/settings/users/{group}', 'verb' => 'GET', 'requirements' => ['group' => '.+'] , 'root' => ''],
		['name' => 'Users#setPreference', 'url' => '/settings/users/preferences/{key}', 'verb' => 'POST' , 'root' => ''],
		['name' => 'LogSettings#download', 'url' => '/settings/admin/log/download', 'verb' => 'GET' , 'root' => ''],
		['name' => 'CheckSetup#setupCheckManager', 'url' => '/settings/setupcheck', 'verb' => 'GET' , 'root' => ''],
		['name' => 'CheckSetup#check', 'url' => '/settings/ajax/checksetup', 'verb' => 'GET' , 'root' => ''],
		['name' => 'CheckSetup#getFailedIntegrityCheckFiles', 'url' => '/settings/integrity/failed', 'verb' => 'GET' , 'root' => ''],
		['name' => 'CheckSetup#rescanFailedIntegrityCheck', 'url' => '/settings/integrity/rescan', 'verb' => 'GET' , 'root' => ''],
		['name' => 'PersonalSettings#index', 'url' => '/settings/user/{section}', 'verb' => 'GET', 'defaults' => ['section' => 'personal-info'] , 'root' => ''],
		['name' => 'AdminSettings#index', 'url' => '/settings/admin/{section}', 'verb' => 'GET', 'defaults' => ['section' => 'server'] , 'root' => ''],
		['name' => 'AdminSettings#form', 'url' => '/settings/admin/{section}', 'verb' => 'GET' , 'root' => ''],
		['name' => 'ChangePassword#changePersonalPassword', 'url' => '/settings/personal/changepassword', 'verb' => 'POST' , 'root' => ''],
		['name' => 'ChangePassword#changeUserPassword', 'url' => '/settings/users/changepassword', 'verb' => 'POST' , 'root' => ''],
		['name' => 'TwoFactorSettings#index', 'url' => '/settings/api/admin/twofactorauth', 'verb' => 'GET' , 'root' => ''],
		['name' => 'TwoFactorSettings#update', 'url' => '/settings/api/admin/twofactorauth', 'verb' => 'PUT' , 'root' => ''],
		['name' => 'AISettings#update', 'url' => '/settings/api/admin/ai', 'verb' => 'PUT' , 'root' => ''],

		['name' => 'Help#help', 'url' => '/settings/help/{mode}', 'verb' => 'GET', 'defaults' => ['mode' => ''] , 'root' => ''],

		['name' => 'WebAuthn#startRegistration', 'url' => '/settings/api/personal/webauthn/registration', 'verb' => 'GET' , 'root' => ''],
		['name' => 'WebAuthn#finishRegistration', 'url' => '/settings/api/personal/webauthn/registration', 'verb' => 'POST' , 'root' => ''],
		['name' => 'WebAuthn#deleteRegistration', 'url' => '/settings/api/personal/webauthn/registration/{id}', 'verb' => 'DELETE' , 'root' => ''],

		['name' => 'Reasons#getPdf', 'url' => '/settings/download/reasons', 'verb' => 'GET', 'root' => ''],
	],
	'ocs' => [
		['name' => 'DeclarativeSettings#setValue', 'url' => '/settings/api/declarative/value', 'verb' => 'POST', 'root' => ''],
		['name' => 'DeclarativeSettings#setSensitiveValue', 'url' => '/settings/api/declarative/value-sensitive', 'verb' => 'POST', 'root' => ''],
		['name' => 'DeclarativeSettings#getForms', 'url' => '/settings/api/declarative/forms', 'verb' => 'GET', 'root' => ''],
	],
];
