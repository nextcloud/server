<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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
namespace OCA\Files_External\Appinfo;

$application = new Application();
$application->registerRoutes(
        $this,
        array(
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

/** @var $this OC\Route\Router */

$this->create('files_external_add_mountpoint', 'ajax/addMountPoint.php')
	->actionInclude('files_external/ajax/addMountPoint.php');
$this->create('files_external_remove_mountpoint', 'ajax/removeMountPoint.php')
	->actionInclude('files_external/ajax/removeMountPoint.php');

$this->create('files_external_add_root_certificate', 'ajax/addRootCertificate.php')
	->actionInclude('files_external/ajax/addRootCertificate.php');
$this->create('files_external_remove_root_certificate', 'ajax/removeRootCertificate.php')
	->actionInclude('files_external/ajax/removeRootCertificate.php');

$this->create('files_external_dropbox', 'ajax/dropbox.php')
	->actionInclude('files_external/ajax/dropbox.php');
$this->create('files_external_google', 'ajax/google.php')
	->actionInclude('files_external/ajax/google.php');


$this->create('files_external_list_applicable', '/applicable')
	->actionInclude('files_external/ajax/applicable.php');

\OC_API::register('get',
		'/apps/files_external/api/v1/mounts',
		array('\OCA\Files\External\Api', 'getUserMounts'),
		'files_external');

