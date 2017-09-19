<?php
namespace OC\Core\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\BigIntMigration;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version13000Date20170919101637 extends BigIntMigration {

	/**
	 * @return array Returns an array with the following structure
	 * ['table1' => ['column1', 'column2'], ...]
	 * @since 13.0.0
	 */
	protected function getColumnsByTable() {
		return [
			'admin_settings' => ['id'],
			'authtoken' => ['id'],
			'bruteforce_attempts' => ['id'],
			'comments' => ['id', 'parent_id', 'topmost_parent_id'],
			'filecache' => ['fileid', 'storage', 'parent', 'mimetype', 'mimepart'],
			'file_locks' => ['id'],
			'jobs' => ['id'],
			'mimetypes' => ['id'],
			'mounts' => ['id'],
			'personal_settings' => ['id'],
			'properties' => ['id'],
			'share' => ['id', 'parent', 'file_source'],
			'storages' => ['numeric_id'],
			'systemtag' => ['id'],
			'systemtag_group' => ['systemtagid'],
			'systemtag_object_mapping' => ['systemtagid'],
			'vcategory' => ['id'],
			'vcategory_to_object' => ['objid', 'categoryid'],
		];
	}

}
