<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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

$installedVersion = \OC::$server->getConfig()->getAppValue('files_external', 'installed_version');

$app = new \OCA\Files_External\AppInfo\Application();

// Migration to db config
if (version_compare($installedVersion, '0.5.0', '<')) {
	$migrator = $app->getContainer()->query('OCA\Files_External\Migration\StorageMigrator');
	$migrator->migrateGlobal();
}
