<?php
 /**
 * @author Thomas Müller
 * @copyright 2015 Thomas Müller deepdiver@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\Db\Migrations;


use Doctrine\DBAL\Migrations\Configuration\Configuration;
use InvalidArgumentException;

trait MigrationTrait {
	/**
	 * @param $appName
	 * @return Configuration
	 */
	protected function buildConfiguration($appName, $connection) {
		if ($appName === 'core') {
			$mc = new Configuration($connection);
			$mc->setMigrationsDirectory(\OC::$SERVERROOT."/core/migrations");
			$mc->setMigrationsNamespace("OC\\Migrations");
			$mc->setMigrationsTableName("core_migration_versions");
			return $mc;
		}
		$appPath = \OC_App::getAppPath($appName);
		if (!$appPath) {
			throw new InvalidArgumentException('Path to app is not defined.');
		}

		$mc = new Configuration($connection);
		$mc->setMigrationsDirectory(\OC::$SERVERROOT."/$appPath/appinfo/migrations");
		$mc->setMigrationsNamespace("OCA\\$appName\\Migrations");
		$mc->setMigrationsTableName("{$appName}_migration_versions");
		return $mc;
	}

}
