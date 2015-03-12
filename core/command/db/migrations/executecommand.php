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
use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand as DBALExecuteCommand;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends DBALExecuteCommand {
	use MigrationTrait;

	/** @var Connection */
	private $ocConnection;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config, Connection $connection) {
		$this->config = $config;
		$this->ocConnection = $connection;

		parent::__construct();
	}

	protected function configure() {
		$this->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on');

		parent::configure();
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$appName = $input->getArgument('app');
		$mc = $this->buildConfiguration($appName, $this->ocConnection);
		$this->setMigrationConfiguration($mc);

		parent::execute($input, $output);
	}

}
