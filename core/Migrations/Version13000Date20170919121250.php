<?php
namespace OC\Core\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version13000Date20170919121250 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}

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

		$table = $schema->getTable('jobs');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('authtoken');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);
		$column = $table->getColumn('type');
		$column->setUnsigned(true);
		$column = $table->getColumn('remember');
		$column->setUnsigned(true);
		$column = $table->getColumn('last_activity');
		$column->setUnsigned(true);
		$column = $table->getColumn('last_check');
		$column->setUnsigned(true);

		$table = $schema->getTable('bruteforce_attempts');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);
		$column = $table->getColumn('occurred');
		$column->setUnsigned(true);

		$table = $schema->getTable('comments');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);
		$column = $table->getColumn('parent_id');
		$column->setUnsigned(true);
		$column = $table->getColumn('topmost_parent_id');
		$column->setUnsigned(true);
		$column = $table->getColumn('children_count');
		$column->setUnsigned(true);

		$table = $schema->getTable('file_locks');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('systemtag');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('systemtag_object_mapping');
		$column = $table->getColumn('systemtagid');
		$column->setUnsigned(true);

		$table = $schema->getTable('systemtag_group');
		$column = $table->getColumn('systemtagid');
		$column->setUnsigned(true);

		$table = $schema->getTable('vcategory');
		$column = $table->getColumn('id');
		$column->setUnsigned(true);

		$table = $schema->getTable('vcategory_to_object');
		$column = $table->getColumn('objid');
		$column->setUnsigned(true);
		$column = $table->getColumn('categoryid');
		$column->setUnsigned(true);

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `Schema`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
	}
}
