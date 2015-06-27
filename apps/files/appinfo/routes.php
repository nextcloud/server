<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Tom Needham <tom@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files\Appinfo;

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
		)
	)
);

/** @var $this \OC\Route\Router */

$this->create('files_index', '/')
	->actionInclude('files/index.php');
$this->create('files_ajax_delete', 'ajax/delete.php')
	->actionInclude('files/ajax/delete.php');
$this->create('files_ajax_download', 'ajax/download.php')
	->actionInclude('files/ajax/download.php');
$this->create('files_ajax_getstoragestats', 'ajax/getstoragestats.php')
	->actionInclude('files/ajax/getstoragestats.php');
$this->create('files_ajax_list', 'ajax/list.php')
	->actionInclude('files/ajax/list.php');
$this->create('files_ajax_mimeicon', 'ajax/mimeicon.php')
	->actionInclude('files/ajax/mimeicon.php');
$this->create('files_ajax_move', 'ajax/move.php')
	->actionInclude('files/ajax/move.php');
$this->create('files_ajax_newfile', 'ajax/newfile.php')
	->actionInclude('files/ajax/newfile.php');
$this->create('files_ajax_newfolder', 'ajax/newfolder.php')
	->actionInclude('files/ajax/newfolder.php');
$this->create('files_ajax_rename', 'ajax/rename.php')
	->actionInclude('files/ajax/rename.php');
$this->create('files_ajax_scan', 'ajax/scan.php')
	->actionInclude('files/ajax/scan.php');
$this->create('files_ajax_upload', 'ajax/upload.php')
	->actionInclude('files/ajax/upload.php');

$this->create('download', 'download{file}')
	->requirements(array('file' => '.*'))
	->actionInclude('files/download.php');
	
// Register with the capabilities API
\OCP\API::register('get', '/cloud/capabilities', array('OCA\Files\Capabilities', 'getCapabilities'), 'files', \OCP\API::USER_AUTH);
