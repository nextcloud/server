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


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand as DBALStatusCommand;
use InvalidArgumentException;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends DBALStatusCommand {

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config, Connection $connection) {
		$this->config = $config;
		$this->ocConnection = $connection;

		parent::__construct();
	}

	protected function configure() {
		parent::configure();

		$this->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$appName = $input->getArgument('app');
		$mc = $this->buildConfiguration($appName);
		$this->setMigrationConfiguration($mc);

		parent::execute($input, $output);
	}

	/**
	 * @param $appName
	 * @return Configuration
	 */
	protected function buildConfiguration($appName) {
		if ($appName === 'core') {
			$mc = new Configuration($this->ocConnection);
			$mc->setMigrationsDirectory(\OC::$SERVERROOT."/core/migrations");
			$mc->setMigrationsNamespace("\\OC\\Migrations");
			$mc->setMigrationsTableName("core_migration_versions");
			return $mc;
		}
		$appPath = \OC_App::getAppPath($appName);
		if (!$appPath) {
			throw new InvalidArgumentException('Path to app is not defined.');
		}

		$mc = new Configuration($this->ocConnection);
		$mc->setMigrationsDirectory(\OC::$SERVERROOT."/$appPath/appinfo/migrations");
		$mc->setMigrationsNamespace("\\OCA\\$appName\\Migrations");
		$mc->setMigrationsTableName("{$appName}_migration_versions");
		return $mc;
	}
}
