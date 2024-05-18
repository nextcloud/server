<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author oparoz <owncloud@interfasys.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
