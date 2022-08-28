<?php

declare(strict_types=1);

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version23000Date20210906132259 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/**
		 * Table was missing a primary key
		 * Therefore it was dropped with Version24000Date20211213081506
		 * and then recreated with a primary key in Version24000Date20211213081604
		 */
		//		/** @var ISchemaWrapper $schema */
		//		$schema = $schemaClosure();
		//
		//		$hasTable = $schema->hasTable(self::TABLE_NAME);
		//
		//		if (!$hasTable) {
		//			$table = $schema->createTable(self::TABLE_NAME);
		//			$table->addColumn('hash', Types::STRING, [
		//				'notnull' => true,
		//				'length' => 128,
		//			]);
		//			$table->addColumn('delete_after', Types::DATETIME, [
		//				'notnull' => true,
		//			]);
		//			$table->addIndex(['hash'], 'ratelimit_hash');
		//			$table->addIndex(['delete_after'], 'ratelimit_delete_after');
		//			return $schema;
		//		}

		return null;
	}
}
