<?php
namespace OCA\DAV\Migration;

use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1006Date20180619154313 extends SimpleMigrationStep {

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

		if (!$schema->hasTable('calendar_invitations')) {
			$table = $schema->createTable('calendar_invitations');

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
			$table->addColumn('recurrenceid', Type::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->addColumn('attendee', Type::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('organizer', Type::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('sequence', Type::BIGINT, [
				'notnull' => false,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('token', Type::STRING, [
				'notnull' => true,
				'length' => 60,
			]);
			$table->addColumn('expiration', Type::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['token'], 'calendar_invitation_tokens');

			return $schema;
		}
	}
}
