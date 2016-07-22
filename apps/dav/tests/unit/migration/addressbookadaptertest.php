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
use OCP\IDBConnection;
use Test\TestCase;

/**
 * Class AddressbookAdapterTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Migration
 */
class AddressbookAdapterTest extends TestCase {

	/** @var IDBConnection */
	private $db;
	/** @var AddressBookAdapter */
	private $adapter;
	/** @var array */
	private $books = [];
	/** @var array */
	private $cards = [];

	public function setUp() {
		parent::setUp();
		$this->db = \OC::$server->getDatabaseConnection();

		$manager = new \OC\DB\MDB2SchemaManager($this->db);
		$manager->createDbFromStructure(__DIR__ . '/contacts_schema.xml');

		$this->adapter = new AddressBookAdapter($this->db);
	}

	public function tearDown() {
		$this->db->dropTable('contacts_addressbooks');
		$this->db->dropTable('contacts_cards');
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
		$builder->insert('contacts_addressbooks')
			->values([
				'userid' => $builder->createNamedParameter('test-user-666'),
				'displayname' => $builder->createNamedParameter('Display Name'),
				'uri' => $builder->createNamedParameter('contacts'),
				'description' => $builder->createNamedParameter('An address book for testing'),
				'ctag' => $builder->createNamedParameter('112233'),
				'active' => $builder->createNamedParameter('1')
			])
			->execute();
		$builder = $this->db->getQueryBuilder();
		$builder->insert('contacts_cards')
			->values([
				'addressbookid' => $builder->createNamedParameter(6666),
				'fullname' => $builder->createNamedParameter('Full Name'),
				'carddata' => $builder->createNamedParameter('datadatadata'),
				'uri' => $builder->createNamedParameter('some-card.vcf'),
				'lastmodified' => $builder->createNamedParameter('112233'),
			])
			->execute();
		$builder = $this->db->getQueryBuilder();
		$builder->insert('share')
			->values([
				'share_type' => $builder->createNamedParameter(1),
				'share_with' => $builder->createNamedParameter('user01'),
				'uid_owner' => $builder->createNamedParameter('user02'),
				'item_type' => $builder->createNamedParameter('addressbook'),
				'item_source' => $builder->createNamedParameter(6666),
				'item_target' => $builder->createNamedParameter('Contacts (user02)'),
			])
			->execute();

		// test the adapter
		$this->adapter->foreachBook('test-user-666', function($row) {
			$this->books[] = $row;
		});
		$this->assertArrayHasKey('id', $this->books[0]);
		$this->assertEquals('test-user-666', $this->books[0]['userid']);
		$this->assertEquals('Display Name', $this->books[0]['displayname']);
		$this->assertEquals('contacts', $this->books[0]['uri']);
		$this->assertEquals('An address book for testing', $this->books[0]['description']);
		$this->assertEquals('112233', $this->books[0]['ctag']);

		$this->adapter->foreachCard(6666, function($row) {
			$this->cards[]= $row;
		});
		$this->assertArrayHasKey('id', $this->cards[0]);
		$this->assertEquals(6666, $this->cards[0]['addressbookid']);

		// test getShares
		$shares = $this->adapter->getShares(6666);
		$this->assertEquals(1, count($shares));

	}

}
