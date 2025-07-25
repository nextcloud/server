<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
return [
	'ocs' => [
		// Apps
		['root' => '/cloud', 'name' => 'Apps#getApps', 'url' => '/apps', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Apps#getAppInfo', 'url' => '/apps/{app}', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Apps#enable', 'url' => '/apps/{app}', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Apps#disable', 'url' => '/apps/{app}', 'verb' => 'DELETE'],

		// Groups
		['root' => '/cloud', 'name' => 'Groups#getGroups', 'url' => '/groups', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Groups#getGroupsDetails', 'url' => '/groups/details', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Groups#getGroupUsers', 'url' => '/groups/{groupId}/users', 'verb' => 'GET', 'requirements' => ['groupId' => '.+']],
		['root' => '/cloud', 'name' => 'Groups#getGroupUsersDetails', 'url' => '/groups/{groupId}/users/details', 'verb' => 'GET', 'requirements' => ['groupId' => '.+']],
		['root' => '/cloud', 'name' => 'Groups#getSubAdminsOfGroup', 'url' => '/groups/{groupId}/subadmins', 'verb' => 'GET', 'requirements' => ['groupId' => '.+']],
		['root' => '/cloud', 'name' => 'Groups#addGroup', 'url' => '/groups', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Groups#getGroup', 'url' => '/groups/{groupId}', 'verb' => 'GET', 'requirements' => ['groupId' => '.+']],
		['root' => '/cloud', 'name' => 'Groups#updateGroup', 'url' => '/groups/{groupId}', 'verb' => 'PUT', 'requirements' => ['groupId' => '.+']],
		['root' => '/cloud', 'name' => 'Groups#deleteGroup', 'url' => '/groups/{groupId}', 'verb' => 'DELETE', 'requirements' => ['groupId' => '.+']],

		// Users
		['root' => '/cloud', 'name' => 'Users#getUsers', 'url' => '/users', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getUsersDetails', 'url' => '/users/details', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getDisabledUsersDetails', 'url' => '/users/disabled', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getLastLoggedInUsers', 'url' => '/users/recent', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#searchByPhoneNumbers', 'url' => '/users/search/by-phone', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Users#addUser', 'url' => '/users', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Users#getUser', 'url' => '/users/{userId}', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getCurrentUser', 'url' => '/user', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getEditableFields', 'url' => '/user/fields', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getEditableFieldsForUser', 'url' => '/user/fields/{userId}', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getEnabledApps', 'url' => '/user/apps', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#editUser', 'url' => '/users/{userId}', 'verb' => 'PUT'],
		['root' => '/cloud', 'name' => 'Users#editUserMultiValue', 'url' => '/users/{userId}/{collectionName}', 'verb' => 'PUT', 'requirements' => ['collectionName' => '^(?!enable$|disable$)[a-zA-Z0-9_]*$']],
		['root' => '/cloud', 'name' => 'Users#wipeUserDevices', 'url' => '/users/{userId}/wipe', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Users#deleteUser', 'url' => '/users/{userId}', 'verb' => 'DELETE'],
		['root' => '/cloud', 'name' => 'Users#enableUser', 'url' => '/users/{userId}/enable', 'verb' => 'PUT'],
		['root' => '/cloud', 'name' => 'Users#disableUser', 'url' => '/users/{userId}/disable', 'verb' => 'PUT'],
		['root' => '/cloud', 'name' => 'Users#getUsersGroups', 'url' => '/users/{userId}/groups', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getUsersGroupsDetails', 'url' => '/users/{userId}/groups/details', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#addToGroup', 'url' => '/users/{userId}/groups', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Users#removeFromGroup', 'url' => '/users/{userId}/groups', 'verb' => 'DELETE'],
		['root' => '/cloud', 'name' => 'Users#getUserSubAdminGroups', 'url' => '/users/{userId}/subadmins', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#getUserSubAdminGroupsDetails', 'url' => '/users/{userId}/subadmins/details', 'verb' => 'GET'],
		['root' => '/cloud', 'name' => 'Users#addSubAdmin', 'url' => '/users/{userId}/subadmins', 'verb' => 'POST'],
		['root' => '/cloud', 'name' => 'Users#removeSubAdmin', 'url' => '/users/{userId}/subadmins', 'verb' => 'DELETE'],
		['root' => '/cloud', 'name' => 'Users#resendWelcomeMessage', 'url' => '/users/{userId}/welcome', 'verb' => 'POST'],

		// Config
		['name' => 'AppConfig#getApps', 'url' => '/api/v1/config/apps', 'verb' => 'GET'],
		['name' => 'AppConfig#getKeys', 'url' => '/api/v1/config/apps/{app}', 'verb' => 'GET'],
		['name' => 'AppConfig#getValue', 'url' => '/api/v1/config/apps/{app}/{key}', 'verb' => 'GET'],
		['name' => 'AppConfig#setValue', 'url' => '/api/v1/config/apps/{app}/{key}', 'verb' => 'POST'],
		['name' => 'AppConfig#deleteKey', 'url' => '/api/v1/config/apps/{app}/{key}', 'verb' => 'DELETE'],

		// Preferences
		['name' => 'Preferences#setPreference', 'url' => '/api/v1/config/users/{appId}/{configKey}', 'verb' => 'POST'],
		['name' => 'Preferences#setMultiplePreferences', 'url' => '/api/v1/config/users/{appId}', 'verb' => 'POST'],
		['name' => 'Preferences#deletePreference', 'url' => '/api/v1/config/users/{appId}/{configKey}', 'verb' => 'DELETE'],
		['name' => 'Preferences#deleteMultiplePreference', 'url' => '/api/v1/config/users/{appId}', 'verb' => 'DELETE'],
	],
	'routes' => [
		// Verification
		['name' => 'Verification#showVerifyMail', 'url' => '/mailVerification/{key}/{token}/{userId}', 'verb' => 'GET'],
		['name' => 'Verification#verifyMail', 'url' => '/mailVerification/{key}/{token}/{userId}', 'verb' => 'POST'],
	]
];
