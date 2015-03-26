<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Tom Needham <tom@owncloud.com>
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

/** @var $this \OCP\Route\IRouter */

$this->create('files_encryption_ajax_adminrecovery', 'ajax/adminrecovery.php')
	->actionInclude('files_encryption/ajax/adminrecovery.php');
$this->create('files_encryption_ajax_changeRecoveryPassword', 'ajax/changeRecoveryPassword.php')
	->actionInclude('files_encryption/ajax/changeRecoveryPassword.php');
$this->create('files_encryption_ajax_getMigrationStatus', 'ajax/getMigrationStatus.php')
	->actionInclude('files_encryption/ajax/getMigrationStatus.php');
$this->create('files_encryption_ajax_updatePrivateKeyPassword', 'ajax/updatePrivateKeyPassword.php')
	->actionInclude('files_encryption/ajax/updatePrivateKeyPassword.php');
$this->create('files_encryption_ajax_userrecovery', 'ajax/userrecovery.php')
	->actionInclude('files_encryption/ajax/userrecovery.php');

// Register with the capabilities API
OC_API::register('get', '/cloud/capabilities', array('OCA\Files_Encryption\Capabilities', 'getCapabilities'), 'files_encryption', OC_API::USER_AUTH);
