<?php

declare(strict_types=1);

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version21000Date20201120141228 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('authtoken')) {
			$table = $schema->getTable('authtoken');
			$loginNameColumn = $table->getColumn('login_name');
			if ($loginNameColumn->getLength() !== 255) {
				$loginNameColumn->setLength(255);
			}
			$table->changeColumn('type', [
				'notnull' => false,
			]);
			$table->changeColumn('remember', [
				'notnull' => false,
			]);
			$table->changeColumn('last_activity', [
				'notnull' => false,
			]);
			$table->changeColumn('last_check', [
				'notnull' => false,
			]);
		}

		if ($schema->hasTable('dav_job_status')) {
			$schema->dropTable('dav_job_status');
		}

		if ($schema->hasTable('systemtag')) {
			$table = $schema->getTable('systemtag');
			if ($table->hasColumn('systemtag')) {
				$table->dropColumn('assignable');
			}
		}

		if ($schema->hasTable('share')) {
			$table = $schema->getTable('share');
			if ($table->hasColumn('attributes')) {
				$table->dropColumn('attributes');
			}
		}

		if ($schema->hasTable('jobs')) {
			$table = $schema->getTable('jobs');
			$table->changeColumn('execution_duration', [
				'notnull' => false,
				'default' => 0,
			]);
		}

		return $schema;
	}
}
