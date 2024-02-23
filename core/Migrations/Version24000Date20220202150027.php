<?php

declare(strict_types=1);

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version24000Date20220202150027 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mounts');
		if (!$table->hasColumn('mount_provider_class')) {
			$table->addColumn('mount_provider_class', Types::STRING, [
				'notnull' => false,
				'length' => 128,
			]);
			$table->addIndex(['mount_provider_class'], 'mounts_class_index');
			return $schema;
		}
		return null;
	}
}
