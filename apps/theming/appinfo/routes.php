<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
return [
	'routes' => [
		[
			'name' => 'Theming#updateAppMenu',
			'url' => '/ajax/updateAppMenu',
			'verb' => 'PUT',
		],
		[
			'name' => 'Theming#updateStylesheet',
			'url' => '/ajax/updateStylesheet',
			'verb' => 'POST'
		],
		[
			'name' => 'Theming#undo',
			'url' => '/ajax/undoChanges',
			'verb' => 'POST'
		],
		[
			'name' => 'Theming#undoAll',
			'url' => '/ajax/undoAllChanges',
			'verb' => 'POST'
		],
		[
			'name' => 'Theming#uploadImage',
			'url' => '/ajax/uploadImage',
			'verb' => 'POST'
		],
		[
			'name' => 'Theming#getThemeStylesheet',
			'url' => '/theme/{themeId}.css',
			'verb' => 'GET',
		],
		[
			'name' => 'Theming#getImage',
			'url' => '/image/{key}',
			'verb' => 'GET',
		],
		[
			'name' => 'Theming#getManifest',
			'url' => '/manifest/{app}',
			'verb' => 'GET',
			'defaults' => ['app' => 'core']
		],
		[
			'name' => 'Icon#getFavicon',
			'url' => '/favicon/{app}',
			'verb' => 'GET',
			'defaults' => ['app' => 'core'],
		],
		[
			'name' => 'Icon#getTouchIcon',
			'url' => '/icon/{app}',
			'verb' => 'GET',
			'defaults' => ['app' => 'core'],
		],
		[
			'name' => 'Icon#getThemedIcon',
			'url' => '/img/{app}/{image}',
			'verb' => 'GET',
			'requirements' => ['image' => '.+']
		],
		[
			'name' => 'userTheme#getBackground',
			'url' => '/background',
			'verb' => 'GET',
		],
		[
			'name' => 'userTheme#setBackground',
			'url' => '/background/{type}',
			'verb' => 'POST',
		],
		[
			'name' => 'userTheme#deleteBackground',
			'url' => '/background/custom',
			'verb' => 'DELETE',
		],
	],
	'ocs' => [
		[
			'name' => 'userTheme#enableTheme',
			'url' => '/api/v1/theme/{themeId}/enable',
			'verb' => 'PUT',
		],
		[
			'name' => 'userTheme#disableTheme',
			'url' => '/api/v1/theme/{themeId}',
			'verb' => 'DELETE',
		],
	]
];
