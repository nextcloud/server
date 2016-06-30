<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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

use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Doctrine\DBAL\Migrations\OutputWriter;

class MigrationConfiguration extends \Doctrine\DBAL\Migrations\Configuration\Configuration {

	function __construct(Connection $connection, OutputWriter $outputWriter = null, MigrationFinderInterface $finder = null) {
		parent::__construct($connection, $outputWriter, $finder);

		$this->setMigrationsTableName($this->getMigrationsTableName());
		$this->setMigrationsColumnName($this->getMigrationsColumnName());
	}

	public function setMigrationsColumnName($columnName) {
		$columnName = $this->getConnection()->getDatabasePlatform()->quoteIdentifier($columnName);
		parent::setMigrationsColumnName($columnName);
	}

	public function setMigrationsTableName($tableName) {
		$tableName = $this->getConnection()->getDatabasePlatform()->quoteIdentifier($tableName);
		parent::setMigrationsTableName($tableName);
	}

}
