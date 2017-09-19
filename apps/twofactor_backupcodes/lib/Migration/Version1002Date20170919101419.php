<?php
namespace OCA\TwoFactorBackupCodes\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\BigIntMigration;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1002Date20170919101419 extends BigIntMigration {

	/**
	 * @return array Returns an array with the following structure
	 * ['table1' => ['column1', 'column2'], ...]
	 * @since 13.0.0
	 */
	protected function getColumnsByTable() {
		return [
			'twofactor_backupcodes' => ['id'],
		];
	}

}
