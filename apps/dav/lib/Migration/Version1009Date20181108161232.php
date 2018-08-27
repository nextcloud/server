<?php
namespace OCA\DAV\Migration;

use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1009Date20181108161232 extends SimpleMigrationStep {
	public function name(): string {
		return 'Add dav_page_cache table';
	}

	public function description(): string {
		return 'Add table to cache webdav multistatus responses for pagination purpose';
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('dav_page_cache')) {
			$table = $schema->createTable('dav_page_cache');

			$table->addColumn('id', Type::BIGINT, [
				'autoincrement' => true
			]);
			$table->addColumn('url_hash', Type::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('token', Type::STRING, [
				'notnull' => true,
				'length' => 32
			]);
			$table->addColumn('result_index', Type::INTEGER, [
				'notnull' => true
			]);
			$table->addColumn('result_value', TYPE::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('insert_time', TYPE::BIGINT, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id'], 'dav_page_cache_id_index');
			$table->addIndex(['token', 'url_hash'], 'dav_page_cache_token_url');
			$table->addUniqueIndex(['token', 'url_hash', 'result_index'], 'dav_page_cache_url_index');
			$table->addIndex(['result_index'], 'dav_page_cache_index');
			$table->addIndex(['insert_time'], 'dav_page_cache_time');
		}

		return $schema;
	}
}
