<?php
namespace OCA\DAV\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1004Date20170919104507 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @return null|Schema
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var Schema $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('addressbooks');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('calendarobjects');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('calendarchanges');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		return $schema;
	}

}
