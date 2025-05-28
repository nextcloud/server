<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\Mapping;

use OCA\User_LDAP\Mapping\AbstractMapping;
use OCP\IDBConnection;
use OCP\Server;

abstract class AbstractMappingTestCase extends \Test\TestCase {
	abstract public function getMapper(IDBConnection $dbMock);

	/**
	 * kiss test on isColNameValid
	 */
	public function testIsColNameValid(): void {
		$dbMock = $this->createMock(IDBConnection::class);
		$mapper = $this->getMapper($dbMock);

		$this->assertTrue($mapper->isColNameValid('ldap_dn'));
		$this->assertFalse($mapper->isColNameValid('foobar'));
	}

	/**
	 * returns an array of test entries with dn, name and uuid as keys
	 * @return array
	 */
	protected static function getTestData(): array {
		return [
			[
				'dn' => 'uid=foobar,dc=example,dc=org',
				'name' => 'Foobar',
				'uuid' => '1111-AAAA-1234-CDEF',
			],
			[
				'dn' => 'uid=barfoo,dc=example,dc=org',
				'name' => 'Barfoo',
				'uuid' => '2222-BBBB-1234-CDEF',
			],
			[
				'dn' => 'uid=barabara,dc=example,dc=org',
				'name' => 'BaraBara',
				'uuid' => '3333-CCCC-1234-CDEF',
			]
		];
	}

	/**
	 * calls map() on the given mapper and asserts result for true
	 * @param AbstractMapping $mapper
	 * @param array $data
	 */
	protected function mapEntries(AbstractMapping $mapper, array $data): void {
		foreach ($data as $entry) {
			$done = $mapper->map($entry['dn'], $entry['name'], $entry['uuid']);
			$this->assertTrue($done);
		}
	}

	/**
	 * initializes environment for a test run and returns an array with
	 * test objects. Preparing environment means that all mappings are cleared
	 * first and then filled with test entries.
	 * @return array 0 = \OCA\User_LDAP\Mapping\AbstractMapping, 1 = array of
	 *               users or groups
	 */
	private function initTest(): array {
		$dbc = Server::get(IDBConnection::class);
		$mapper = $this->getMapper($dbc);
		$data = $this->getTestData();
		// make sure DB is pristine, then fill it with test entries
		$mapper->clear();
		$this->mapEntries($mapper, $data);

		return [$mapper, $data];
	}

	/**
	 * tests map() method with input that should result in not-mapping.
	 * Hint: successful mapping is tested inherently with mapEntries().
	 */
	public function testMap(): void {
		[$mapper, $data] = $this->initTest();

		// test that mapping will not happen when it shall not
		$tooLongDN = 'uid=joann,ou=Secret Small Specialized Department,ou=Some Tremendously Important Department,ou=Another Very Important Department,ou=Pretty Meaningful Derpartment,ou=Quite Broad And General Department,ou=The Topmost Department,dc=hugelysuccessfulcompany,dc=com';
		$paramKeys = ['', 'dn', 'name', 'uuid', $tooLongDN];
		foreach ($paramKeys as $key) {
			$failEntry = $data[0];
			if (!empty($key)) {
				$failEntry[$key] = 'do-not-get-mapped';
			}
			$isMapped = $mapper->map($failEntry['dn'], $failEntry['name'], $failEntry['uuid']);
			$this->assertFalse($isMapped);
		}
	}

	/**
	 * tests unmap() for both successful and unsuccessful removing of
	 * mapping entries
	 */
	public function testUnmap(): void {
		[$mapper, $data] = $this->initTest();

		foreach ($data as $entry) {
			$fdnBefore = $mapper->getDNByName($entry['name']);
			$result = $mapper->unmap($entry['name']);
			$fdnAfter = $mapper->getDNByName($entry['name']);
			$this->assertTrue($result);
			$this->assertSame($fdnBefore, $entry['dn']);
			$this->assertFalse($fdnAfter);
		}

		$result = $mapper->unmap('notAnEntry');
		$this->assertFalse($result);
	}

	/**
	 * tests getDNByName(), getNameByDN() and getNameByUUID() for successful
	 * and unsuccessful requests.
	 */
	public function testGetMethods(): void {
		[$mapper, $data] = $this->initTest();

		foreach ($data as $entry) {
			$fdn = $mapper->getDNByName($entry['name']);
			$this->assertSame($fdn, $entry['dn']);
		}
		$fdn = $mapper->getDNByName('nosuchname');
		$this->assertFalse($fdn);

		foreach ($data as $entry) {
			$name = $mapper->getNameByDN($entry['dn']);
			$this->assertSame($name, $entry['name']);
		}
		$name = $mapper->getNameByDN('nosuchdn');
		$this->assertFalse($name);

		foreach ($data as $entry) {
			$name = $mapper->getNameByUUID($entry['uuid']);
			$this->assertSame($name, $entry['name']);
		}
		$name = $mapper->getNameByUUID('nosuchuuid');
		$this->assertFalse($name);
	}

	/**
	 * tests getNamesBySearch() for successful and unsuccessful requests.
	 */
	public function testSearch(): void {
		[$mapper,] = $this->initTest();

		$names = $mapper->getNamesBySearch('oo', '%', '%');
		$this->assertIsArray($names);
		$this->assertSame(2, count($names));
		$this->assertContains('Foobar', $names);
		$this->assertContains('Barfoo', $names);
		$names = $mapper->getNamesBySearch('nada');
		$this->assertIsArray($names);
		$this->assertCount(0, $names);
	}

	/**
	 * tests setDNbyUUID() for successful and unsuccessful update.
	 */
	public function testSetDNMethod(): void {
		[$mapper, $data] = $this->initTest();

		$newDN = 'uid=modified,dc=example,dc=org';
		$done = $mapper->setDNbyUUID($newDN, $data[0]['uuid']);
		$this->assertTrue($done);
		$fdn = $mapper->getDNByName($data[0]['name']);
		$this->assertSame($fdn, $newDN);

		$newDN = 'uid=notme,dc=example,dc=org';
		$done = $mapper->setDNbyUUID($newDN, 'iamnothere');
		$this->assertFalse($done);
		$name = $mapper->getNameByDN($newDN);
		$this->assertFalse($name);
	}

	/**
	 * tests setUUIDbyDN() for successful and unsuccessful update.
	 */
	public function testSetUUIDMethod(): void {
		/** @var AbstractMapping $mapper */
		[$mapper, $data] = $this->initTest();

		$newUUID = 'ABC737-DEF754';

		$done = $mapper->setUUIDbyDN($newUUID, 'uid=notme,dc=example,dc=org');
		$this->assertFalse($done);
		$name = $mapper->getNameByUUID($newUUID);
		$this->assertFalse($name);

		$done = $mapper->setUUIDbyDN($newUUID, $data[0]['dn']);
		$this->assertTrue($done);
		$uuid = $mapper->getUUIDByDN($data[0]['dn']);
		$this->assertSame($uuid, $newUUID);
	}

	/**
	 * tests clear() for successful update.
	 */
	public function testClear(): void {
		[$mapper, $data] = $this->initTest();

		$done = $mapper->clear();
		$this->assertTrue($done);
		foreach ($data as $entry) {
			$name = $mapper->getNameByUUID($entry['uuid']);
			$this->assertFalse($name);
		}
	}

	/**
	 * tests clear() for successful update.
	 */
	public function testClearCb(): void {
		[$mapper, $data] = $this->initTest();

		$callbackCalls = 0;
		$test = $this;

		$callback = function (string $id) use ($test, &$callbackCalls): void {
			$test->assertTrue(trim($id) !== '');
			$callbackCalls++;
		};

		$done = $mapper->clearCb($callback, $callback);
		$this->assertTrue($done);
		$this->assertSame(count($data) * 2, $callbackCalls);
		foreach ($data as $entry) {
			$name = $mapper->getNameByUUID($entry['uuid']);
			$this->assertFalse($name);
		}
	}

	/**
	 * tests getList() method
	 */
	public function testList(): void {
		[$mapper, $data] = $this->initTest();

		// get all entries without specifying offset or limit
		$results = $mapper->getList();
		$this->assertCount(3, $results);

		// get all-1 entries by specifying offset, and an high limit
		// specifying only offset without limit will not work by underlying lib
		$results = $mapper->getList(1, 999);
		$this->assertCount(count($data) - 1, $results);

		// get first 2 entries by limit, but not offset
		$results = $mapper->getList(0, 2);
		$this->assertCount(2, $results);

		// get 2nd entry by specifying both offset and limit
		$results = $mapper->getList(1, 1);
		$this->assertCount(1, $results);
	}

	public function testGetListOfIdsByDn(): void {
		/** @var AbstractMapping $mapper */
		[$mapper,] = $this->initTest();

		$listOfDNs = [];
		for ($i = 0; $i < 66640; $i++) {
			// Postgres has a limit of 65535 values in a single IN list
			$name = 'as_' . $i;
			$dn = 'uid=' . $name . ',dc=example,dc=org';
			$listOfDNs[] = $dn;
			if ($i % 20 === 0) {
				$mapper->map($dn, $name, 'fake-uuid-' . $i);
			}
		}

		$result = $mapper->getListOfIdsByDn($listOfDNs);
		$this->assertCount(66640 / 20, $result);
	}
}
