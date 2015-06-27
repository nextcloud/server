<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
$this->create('core_ajax_trashbin_preview', 'ajax/preview.php')
	->actionInclude('files_trashbin/ajax/preview.php');
$this->create('files_trashbin_ajax_delete', 'ajax/delete.php')
	->actionInclude('files_trashbin/ajax/delete.php');
$this->create('files_trashbin_ajax_isEmpty', 'ajax/isEmpty.php')
	->actionInclude('files_trashbin/ajax/isEmpty.php');
$this->create('files_trashbin_ajax_list', 'ajax/list.php')
	->actionInclude('files_trashbin/ajax/list.php');
$this->create('files_trashbin_ajax_undelete', 'ajax/undelete.php')
	->actionInclude('files_trashbin/ajax/undelete.php');


// Register with the capabilities API
\OCP\API::register('get', '/cloud/capabilities', array('OCA\Files_Trashbin\Capabilities', 'getCapabilities'), 'files_trashbin', \OCP\API::USER_AUTH);
