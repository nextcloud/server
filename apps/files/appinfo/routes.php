<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
	array(
		'routes' => array(
			array(
				'name' => 'API#getThumbnail',
				'url' => '/api/v1/thumbnail/{x}/{y}/{file}',
				'verb' => 'GET',
				'requirements' => array('file' => '.+')
			),
			array(
				'name' => 'API#updateFileTags',
				'url' => '/api/v1/files/{path}',
				'verb' => 'POST',
				'requirements' => array('path' => '.+'),
			),
			array(
				'name' => 'API#getFilesByTag',
				'url' => '/api/v1/tags/{tagName}/files',
				'verb' => 'GET',
				'requirements' => array('tagName' => '.+'),
			),
			array(
				'name' => 'API#updateFileSorting',
				'url' => '/api/v1/sorting',
				'verb' => 'POST'
			),
			array(
				'name' => 'API#showHiddenFiles',
				'url' => '/api/v1/showhidden',
				'verb' => 'POST'
			),
			[
				'name' => 'view#index',
				'url' => '/',
				'verb' => 'GET',
			],
			[
				'name' => 'settings#setMaxUploadSize',
				'url' => '/settings/maxUpload',
				'verb' => 'POST',
			]
		)
	)
);

/** @var $this \OC\Route\Router */

$this->create('files_ajax_delete', 'ajax/delete.php')
	->actionInclude('files/ajax/delete.php');
$this->create('files_ajax_download', 'ajax/download.php')
	->actionInclude('files/ajax/download.php');
$this->create('files_ajax_getstoragestats', 'ajax/getstoragestats.php')
	->actionInclude('files/ajax/getstoragestats.php');
$this->create('files_ajax_list', 'ajax/list.php')
	->actionInclude('files/ajax/list.php');
$this->create('files_ajax_move', 'ajax/move.php')
	->actionInclude('files/ajax/move.php');
$this->create('files_ajax_newfile', 'ajax/newfile.php')
	->actionInclude('files/ajax/newfile.php');
$this->create('files_ajax_newfolder', 'ajax/newfolder.php')
	->actionInclude('files/ajax/newfolder.php');
$this->create('files_ajax_rename', 'ajax/rename.php')
	->actionInclude('files/ajax/rename.php');
$this->create('files_ajax_upload', 'ajax/upload.php')
	->actionInclude('files/ajax/upload.php');

$this->create('download', 'download{file}')
	->requirements(array('file' => '.*'))
	->actionInclude('files/download.php');

