<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\Files_Encryption\Util;

\OCP\JSON::checkAppEnabled('files_encryption');

$loginname = isset($_POST['user']) ? (string)$_POST['user'] : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

$migrationStatus = Util::MIGRATION_COMPLETED;

if ($loginname !== '' && $password !== '') {
	$username = \OCP\User::checkPassword($loginname, $password);
	if ($username) {
		$util = new Util(new \OC\Files\View('/'), $username);
		$migrationStatus = $util->getMigrationStatus();
	}
}

\OCP\JSON::success(array('data' => array('migrationStatus' => $migrationStatus)));
