<?php
/**
 * ownCloud - External Storage Routes
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

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

OC_API::register('get',
		'/apps/files_external/api/v1/mounts',
		array('\OCA\Files\External\Api', 'getUserMounts'),
		'files_external');

