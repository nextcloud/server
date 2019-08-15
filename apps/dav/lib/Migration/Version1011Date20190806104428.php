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
class Version1011Date20190806104428 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('dav_cal_proxy');
		$table->addColumn('id', Type::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 11,
			'unsigned' => true,
		]);
		$table->addColumn('owner_id', Type::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('proxy_id', Type::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('permissions', Type::INTEGER, [
			'notnull' => false,
			'length' => 4,
			'unsigned' => true,
		]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['owner_id', 'proxy_id', 'permissions'], 'dav_cal_proxy_uidx');
		$table->addIndex(['owner_id'], 'dav_cal_proxy_ioid');
		$table->addIndex(['proxy_id'], 'dav_cal_proxy_ipid');

		return $schema;
	}
}
