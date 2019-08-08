<?php

declare(strict_types=1);

namespace OCA\WorkflowEngine\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version2019Date20190808074233 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('flow_checks')) {
			$table = $schema->createTable('flow_checks');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('class', Type::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$table->addColumn('operator', Type::STRING, [
				'notnull' => true,
				'length' => 16,
			]);
			$table->addColumn('value', Type::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('hash', Type::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['hash'], 'flow_unique_hash');
		}

		if (!$schema->hasTable('flow_operations')) {
			$table = $schema->createTable('flow_operations');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('class', Type::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$table->addColumn('name', Type::STRING, [
				'notnull' => true,
				'length' => 256,
			]);
			$table->addColumn('checks', Type::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('operation', Type::TEXT, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('flow_operations_scope')) {
			$table = $schema->createTable('flow_operations_scope');
			$table->addColumn('id', Type::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('operation_id', Type::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('type', Type::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('value', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['operation_id', 'type', 'value'], 'flow_unique_scope');
		}

		return $schema;
	}
}
