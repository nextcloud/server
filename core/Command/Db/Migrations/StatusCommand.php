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

namespace OC\Core\Command\Db\Migrations;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand as DBALStatusCommand;
use OC\DB\MigrationService;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends DBALStatusCommand {

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
		parent::configure();

		$this->addArgument('app', InputArgument::REQUIRED, 'Name of the app this migration command shall work on');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$appName = $input->getArgument('app');
		$ms = new MigrationService();
		$mc = $ms->buildConfiguration($appName, $this->ocConnection);
		$this->setMigrationConfiguration($mc);

		parent::execute($input, $output);
	}

}
