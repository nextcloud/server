<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
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

namespace OC\DB;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;
use OCP\IDBConnection;

class MigrationService {

	/**
	 * @param string $appName
	 * @param IDBConnection $connection
	 * @return Configuration
	 */
	public function buildConfiguration($appName, $connection) {
		if ($appName === 'core') {
			$migrationsPath = \OC::$SERVERROOT . '/core/Migrations';
			$migrationsNamespace = 'OC\\Migrations';
		} else {
			$appPath = \OC_App::getAppPath($appName);
			if (!$appPath) {
				throw new \InvalidArgumentException('Path to app is not defined.');
			}
			$migrationsPath = "$appPath/appinfo/Migrations";
			$migrationsNamespace = "OCA\\$appName\\Migrations";
		}

		if (!is_dir($migrationsPath)) {
			mkdir($migrationsPath);
		}
		$prefix = $connection->getPrefix();
		$mc = new MigrationConfiguration($connection);
		$mc->setMigrationsDirectory($migrationsPath);
		$mc->setMigrationsNamespace($migrationsNamespace);
		$mc->setMigrationsTableName("{$prefix}{$appName}_migration_versions");
		return $mc;
	}

	/**
	 * @param Configuration $migrationConfiguration
	 * @param bool $noMigrationException
	 */
	public function migrate($migrationConfiguration, $noMigrationException = false) {
		$migrationConfiguration->setOutputWriter(new OutputWriter(function ($message){
			\OCP\Util::writeLog('migrations', $message, \OCP\Util::INFO);
		}));

		$migration = new Migration($migrationConfiguration);
		$migration->setNoMigrationException($noMigrationException);
		$migration->migrate();
	}
}
