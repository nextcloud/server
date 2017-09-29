<?php
namespace OCA\DAV\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\BigIntMigration;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1004Date20170926103422 extends BigIntMigration {

	/**
	 * @return array Returns an array with the following structure
	 * ['table1' => ['column1', 'column2'], ...]
	 * @since 13.0.0
	 */
	protected function getColumnsByTable() {
		return [
			'addressbooks' => ['id'],
			'addressbookchanges' => ['id', 'addressbookid'],
			'calendars' => ['id'],
			'calendarchanges' => ['id', 'calendarid'],
			'calendarobjects' => ['id', 'calendarid'],
			'calendarobjects_props' => ['id', 'calendarid', 'objectid'],
			'calendarsubscriptions' => ['id'],
			'cards' => ['id', 'addressbookid'],
			'cards_properties' => ['id', 'addressbookid', 'cardid'],
			'dav_shares' => ['id', 'resourceid'],
			'schedulingobjects' => ['id'],
		];
	}

}
