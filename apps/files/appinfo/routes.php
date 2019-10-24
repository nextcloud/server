<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\Files\AppInfo;

$application = new Application();
$application->registerRoutes(
	$this,
	[
		'routes' => [
			[
				'name' => 'API#getThumbnail',
				'url' => '/api/v1/thumbnail/{x}/{y}/{file}',
				'verb' => 'GET',
				'requirements' => ['file' => '.+']
			],
			[
				'name' => 'API#updateFileTags',
				'url' => '/api/v1/files/{path}',
				'verb' => 'POST',
				'requirements' => ['path' => '.+'],
			],
			[
				'name' => 'API#getRecentFiles',
				'url' => '/api/v1/recent/',
				'verb' => 'GET'
			],
			[
				'name' => 'API#updateFileSorting',
				'url' => '/api/v1/sorting',
				'verb' => 'POST'
			],
			[
				'name' => 'API#showHiddenFiles',
				'url' => '/api/v1/showhidden',
				'verb' => 'POST'
			],
			[
				'name' => 'API#showGridView',
				'url' => '/api/v1/showgridview',
				'verb' => 'POST'
			],
			[
				'name' => 'API#getGridView',
				'url' => '/api/v1/showgridview',
				'verb' => 'GET'
			],
			[
				'name' => 'view#index',
				'url' => '/',
				'verb' => 'GET',
			],
			[
				'name' => 'ajax#getStorageStats',
				'url' => '/ajax/getstoragestats.php',
				'verb' => 'GET',
			],
			[
				'name' => 'API#toggleShowFolder',
				'url' => '/api/v1/toggleShowFolder/{key}',
				'verb' => 'POST'
			],
			[
				'name' => 'API#getNodeType',
				'url' => '/api/v1/quickaccess/get/NodeType',
				'verb' => 'GET',
			],
		]
	]
);

/** @var $this \OC\Route\Router */

$this->create('files_ajax_download', 'ajax/download.php')
	->actionInclude('files/ajax/download.php');
$this->create('files_ajax_list', 'ajax/list.php')
	->actionInclude('files/ajax/list.php');
