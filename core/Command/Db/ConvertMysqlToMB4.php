<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OC\Core\Command\Db;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OC\Migration\ConsoleOutput;
use OC\Repair\Collation;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\ILogger;
use OCP\IURLGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertMysqlToMB4 extends Command {
	/** @var IConfig */
	private $config;

	/** @var IDBConnection */
	private $connection;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/**
	 * @param IConfig $config
	 * @param IDBConnection $connection
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, IDBConnection $connection, IURLGenerator $urlGenerator, ILogger $logger) {
		$this->config = $config;
		$this->connection = $connection;
		$this->urlGenerator = $urlGenerator;
		$this->logger = $logger;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('db:convert-mysql-charset')
			->setDescription('Convert charset of MySQL/MariaDB to use utf8mb4');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if (!$this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
			$output->writeln("This command is only valid for MySQL/MariaDB databases.");
			return 1;
		}

		$oldValue = $this->config->getSystemValue('mysql.utf8mb4', false);
		// enable charset
		$this->config->setSystemValue('mysql.utf8mb4', true);

		if (!$this->connection->supports4ByteText()) {
			$url = $this->urlGenerator->linkToDocs('admin-mysql-utf8mb4');
			$output->writeln("The database is not properly setup to use the charset utf8mb4.");
			$output->writeln("For more information please read the documentation at $url");
			$this->config->setSystemValue('mysql.utf8mb4', $oldValue);
			return 1;
		}

		// run conversion
		$coll = new Collation($this->config, $this->logger, $this->connection, false);
		$coll->run(new ConsoleOutput($output));

		return 0;
	}
}
