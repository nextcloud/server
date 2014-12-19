<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

use Doctrine\DBAL\Schema\Schema;

class MsSqlMigrator extends Migrator {

	/**
	 * @param \Doctrine\DBAL\Schema\Schema $targetSchema
	 */
	public function migrate(Schema $targetSchema) {
		throw new MigrationException('',
			'Database migration is required to continue operation. This feature is provided within the Enterprise Edition.');
	}

}
