<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\DB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use function preg_match;

class Migrator {
	/** @var Connection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	private ?IEventDispatcher $dispatcher;

	/** @var bool */
	private $noEmit = false;

	public function __construct(Connection $connection,
		IConfig $config,
		?IEventDispatcher $dispatcher = null) {
		$this->connection = $connection;
		$this->config = $config;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @throws Exception
	 */
	public function migrate(Schema $targetSchema) {
		$this->noEmit = true;
		$this->applySchema($targetSchema);
	}

	/**
	 * @return string
	 */
	public function generateChangeScript(Schema $targetSchema) {
		$schemaDiff = $this->getDiff($targetSchema, $this->connection);

		$script = '';
		$sqls = $this->connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);
		foreach ($sqls as $sql) {
			$script .= $this->convertStatementToScript($sql);
		}

		return $script;
	}

	/**
	 * @throws Exception
	 */
	public function createSchema() {
		$this->connection->getConfiguration()->setSchemaAssetsFilter(function ($asset) {
			/** @var string|AbstractAsset $asset */
			$filterExpression = $this->getFilterExpression();
			if ($asset instanceof AbstractAsset) {
				return preg_match($filterExpression, $asset->getName()) === 1;
			}
			return preg_match($filterExpression, $asset) === 1;
		});
		return $this->connection->createSchemaManager()->introspectSchema();
	}

	/**
	 * @return SchemaDiff
	 */
	protected function getDiff(Schema $targetSchema, Connection $connection) {
		// Adjust STRING columns with a length higher than 4000 to TEXT (clob)
		// for consistency between the supported databases and
		// old vs. new installations.
		foreach ($targetSchema->getTables() as $table) {
			foreach ($table->getColumns() as $column) {
				if ($column->getType() instanceof StringType) {
					if ($column->getLength() > 4000) {
						$column->setType(Type::getType('text'));
						$column->setLength(null);
					}
				}
			}
		}

		$this->connection->getConfiguration()->setSchemaAssetsFilter(function ($asset) {
			/** @var string|AbstractAsset $asset */
			$filterExpression = $this->getFilterExpression();
			if ($asset instanceof AbstractAsset) {
				return preg_match($filterExpression, $asset->getName()) === 1;
			}
			return preg_match($filterExpression, $asset) === 1;
		});
		$sourceSchema = $connection->createSchemaManager()->introspectSchema();

		// remove tables we don't know about
		foreach ($sourceSchema->getTables() as $table) {
			if (!$targetSchema->hasTable($table->getName())) {
				$sourceSchema->dropTable($table->getName());
			}
		}
		// remove sequences we don't know about
		foreach ($sourceSchema->getSequences() as $table) {
			if (!$targetSchema->hasSequence($table->getName())) {
				$sourceSchema->dropSequence($table->getName());
			}
		}

		$comparator = $connection->createSchemaManager()->createComparator();
		return $comparator->compareSchemas($sourceSchema, $targetSchema);
	}

	/**
	 * @throws Exception
	 */
	protected function applySchema(Schema $targetSchema, Connection $connection = null) {
		if (is_null($connection)) {
			$connection = $this->connection;
		}

		$schemaDiff = $this->getDiff($targetSchema, $connection);

		if (!$connection->getDatabasePlatform() instanceof MySQLPlatform) {
			$connection->beginTransaction();
		}
		$sqls = $connection->getDatabasePlatform()->getAlterSchemaSQL($schemaDiff);
		$step = 0;
		foreach ($sqls as $sql) {
			$this->emit($sql, $step++, count($sqls));
			$connection->executeQuery($sql);
		}
		if (!$connection->getDatabasePlatform() instanceof MySQLPlatform) {
			$connection->commit();
		}
	}

	/**
	 * @param $statement
	 * @return string
	 */
	protected function convertStatementToScript($statement) {
		$script = $statement . ';';
		$script .= PHP_EOL;
		$script .= PHP_EOL;
		return $script;
	}

	protected function getFilterExpression() {
		return '/^' . preg_quote($this->config->getSystemValueString('dbtableprefix', 'oc_'), '/') . '/';
	}

	protected function emit(string $sql, int $step, int $max): void {
		if ($this->noEmit) {
			return;
		}
		if (is_null($this->dispatcher)) {
			return;
		}
		$this->dispatcher->dispatchTyped(new MigratorExecuteSqlEvent($sql, $step, $max));
	}
}
