<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\Command;

use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractExpireChanges
 *
 * @package OCA\DAV\Command
 */
abstract class AbstractExpireChanges extends Command {

	/** @var IDBConnection */
	private $db;

	/** @var string */
	private $commandName;

	/** @var string */
	private $tableName;

	/**
	 * AbstractExpireChanges constructor.
	 *
	 * @param IDBConnection $db
	 * @param String $commandName
	 * @param String $tableName
	 */
	public function __construct(IDBConnection $db, $commandName, $tableName) {
		$this->db = $db;
		$this->commandName = $commandName;
		$this->tableName = $tableName;

		// The constructor of Command calls configure,
		// so we have to set commandName and tableName in advance
		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	public function configure() {
		$this
			->setName('dav:' . $this->commandName)
			->setDescription('Clean up ' . $this->tableName . ' if it grows too big')
			->addOption('token',
				null,
				InputArgument::OPTIONAL,
				'Most recent synctoken to keep, takes precedence over size')
			->addOption('size',
				null,
				InputArgument::OPTIONAL,
				'Maximum amount of rows to keep',
				1000);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(InputInterface $input, OutputInterface $output) {
		$token = $input->getOption('token');
		if (!$token) {
			$size = $input->getOption('size');

			$query = $this->db->getQueryBuilder();
			$result = $query->select(['synctoken'])
				->from($this->tableName)
				->orderBy('synctoken', 'DESC')
				->setFirstResult($size - 1)
				->setMaxResults(1)
				->execute()
				->fetchColumn(0);

			if ($result === false) {
				$output->writeln("<info>Table has fewer than $size rows. Nothing to do.</info>");
				return;
			}

			$token = $result;
		}

		$deleteQuery = $this->db->getQueryBuilder();
		$rowCount = $deleteQuery->delete($this->tableName)
			->where($deleteQuery->expr()->lt('synctoken',
				$deleteQuery->createNamedParameter((int) $token)))
			->execute();

		$output->writeln("<info>Deleted $rowCount rows</info>");
	}
}
