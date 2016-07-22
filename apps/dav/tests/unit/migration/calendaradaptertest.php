<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\Unit\Migration;

use DomainException;
use OCA\Dav\Migration\AddressBookAdapter;
use OCA\Dav\Migration\CalendarAdapter;
use OCP\IDBConnection;
use Test\TestCase;

/**
 * Class CalendarAdapterTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Migration
 */
class CalendarAdapterTest extends TestCase {

	/** @var IDBConnection */
	private $db;
	/** @var CalendarAdapter */
	private $adapter;
	/** @var array */
	private $cals = [];
	/** @var array */
	private $calObjs = [];

	public function setUp() {
		parent::setUp();
		$this->db = \OC::$server->getDatabaseConnection();

		$manager = new \OC\DB\MDB2SchemaManager($this->db);
		$manager->createDbFromStructure(__DIR__ . '/calendar_schema.xml');

		$this->adapter = new CalendarAdapter($this->db);
	}

	public function tearDown() {
		$this->db->dropTable('clndr_calendars');
		$this->db->dropTable('clndr_objects');
		parent::tearDown();
	}

	/**
	 * @expectedException DomainException
	 */
	public function testOldTablesDoNotExist() {
		$adapter = new AddressBookAdapter(\OC::$server->getDatabaseConnection(), 'crazy_table_that_does_no_exist');
		$adapter->setup();
	}

	public function test() {

		// insert test data
		$builder = $this->db->getQueryBuilder();
		$builder->insert('clndr_calendars')
			->values([
				'userid' => $builder->createNamedParameter('test-user-666'),
				'displayname' => $builder->createNamedParameter('Display Name'),
				'uri' => $builder->createNamedParameter('events'),
				'ctag' => $builder->createNamedParameter('112233'),
				'active' => $builder->createNamedParameter('1')
			])
			->execute();
		$builder = $this->db->getQueryBuilder();
		$builder->insert('clndr_objects')
			->values([
				'calendarid' => $builder->createNamedParameter(6666),
				'objecttype' => $builder->createNamedParameter('VEVENT'),
				'startdate' => $builder->createNamedParameter(new \DateTime(), 'datetime'),
				'enddate' => $builder->createNamedParameter(new \DateTime(), 'datetime'),
				'repeating' => $builder->createNamedParameter(0),
				'summary' => $builder->createNamedParameter('Something crazy will happen'),
				'uri' => $builder->createNamedParameter('event.ics'),
				'lastmodified' => $builder->createNamedParameter('112233'),
			])
			->execute();
		$builder = $this->db->getQueryBuilder();
		$builder->insert('share')
			->values([
				'share_type' => $builder->createNamedParameter(1),
				'share_with' => $builder->createNamedParameter('user01'),
				'uid_owner' => $builder->createNamedParameter('user02'),
				'item_type' => $builder->createNamedParameter('calendar'),
				'item_source' => $builder->createNamedParameter(6666),
				'item_target' => $builder->createNamedParameter('Contacts (user02)'),
			])
			->execute();

		// test the adapter
		$this->adapter->foreachCalendar('test-user-666', function($row) {
			$this->cals[] = $row;
		});
		$this->assertArrayHasKey('id', $this->cals[0]);
		$this->assertEquals('test-user-666', $this->cals[0]['userid']);
		$this->assertEquals('Display Name', $this->cals[0]['displayname']);
		$this->assertEquals('events', $this->cals[0]['uri']);
		$this->assertEquals('112233', $this->cals[0]['ctag']);

		$this->adapter->foreachCalendarObject(6666, function($row) {
			$this->calObjs[]= $row;
		});
		$this->assertArrayHasKey('id', $this->calObjs[0]);
		$this->assertEquals(6666, $this->calObjs[0]['calendarid']);

		// test getShares
		$shares = $this->adapter->getShares(6666);
		$this->assertEquals(1, count($shares));

	}

}
