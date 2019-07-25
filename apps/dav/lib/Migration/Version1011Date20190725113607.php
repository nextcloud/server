<?php

declare(strict_types=1);

namespace OCA\DAV\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1011Date20190725113607 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$types = ['resource', 'room'];
		foreach($types as $type) {
			if (!$schema->hasTable($this->getMetadataTableName($type))) {
				$table = $schema->createTable($this->getMetadataTableName($type));

				$table->addColumn('id', Type::BIGINT, [
					'autoincrement' => true,
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]);
				$table->addColumn($type . '_id', Type::BIGINT, [
					'notnull' => true,
					'length' => 11,
					'unsigned' => true,
				]);
				$table->addColumn('key', Type::STRING, [
					'notnull' => true,
					'length' => 255,
				]);
				$table->addColumn('value', Type::STRING, [
					'notnull' => false,
					'length' => 4000,
				]);

				$table->setPrimaryKey(['id']);
				$table->addIndex([$type . '_id', 'key'], $this->getMetadataTableName($type) . '_idk');
			}
		}

		return $schema;
	}

	/**
	 * @param string $type
	 * @return string
	 */
	private function getMetadataTableName(string $type):string {
		return 'calendar_' . $type . 's_md';
	}
}
