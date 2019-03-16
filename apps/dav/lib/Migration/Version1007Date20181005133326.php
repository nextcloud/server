<?php

declare(strict_types=1);

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1007Date20181005133326 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('calendar_reminders')) {
			$table = $schema->createTable('calendar_reminders');

			$table->addColumn('id', Type::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('uid', Type::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('calendarid', Type::BIGINT, [
				'notnull' => false,
				'length' => 11,
			]);
			$table->addColumn('objecturi', Type::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('type', Type::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('notificationdate', Type::DATETIME, [
				'notnull' => false,
			]);
			$table->addColumn('eventstartdate', Type::DATETIME, [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['calendarid'], 'calendar_reminder_calendars');

			return $schema;
		}
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
