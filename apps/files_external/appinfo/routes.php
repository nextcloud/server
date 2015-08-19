<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Ross Nicoll <jrn@jrn.me.uk>
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

namespace OCA\Files_External\AppInfo;

/**
 * @var $this \OC\Route\Router
 **/
\OC_Mount_Config::$app->registerRoutes(
	$this,
	array(
		'resources' => array(
			'global_storages' => array('url' => '/globalstorages'),
			'user_storages' => array('url' => '/userstorages'),
		),
		'routes' => array(
			array(
				'name' => 'Ajax#getSshKeys',
				'url' => '/ajax/sftp_key.php',
				'verb' => 'POST',
				'requirements' => array()
			)
		)
	)
);

$this->create('files_external_dropbox', 'ajax/dropbox.php')
	->actionInclude('files_external/ajax/dropbox.php');
$this->create('files_external_google', 'ajax/google.php')
	->actionInclude('files_external/ajax/google.php');


$this->create('files_external_list_applicable', '/applicable')
	->actionInclude('files_external/ajax/applicable.php');

\OCP\API::register('get',
		'/apps/files_external/api/v1/mounts',
		array('\OCA\Files\External\Api', 'getUserMounts'),
		'files_external');

