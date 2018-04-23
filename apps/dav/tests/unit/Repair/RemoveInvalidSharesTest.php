<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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


namespace OCA\DAV\Tests\Unit\Repair;


use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\Repair\RemoveInvalidShares;
use OCP\Migration\IOutput;
use Test\TestCase;

/**
 * Class RemoveInvalidSharesTest
 *
 * @package OCA\DAV\Tests\Unit\Repair
 * @group DB
 */
class RemoveInvalidSharesTest extends TestCase {

	public function setUp() {
		parent::setUp();
		$db = \OC::$server->getDatabaseConnection();

		$db->insertIfNotExist('*PREFIX*dav_shares', [
			'principaluri' => 'principal:unknown',
			'type' => 'calendar',
			'access' => 2,
			'resourceid' => 666,
		]);
	}

	public function test() {
		$db = \OC::$server->getDatabaseConnection();
		/** @var Principal | \PHPUnit_Framework_MockObject_MockObject $principal */
		$principal = $this->createMock(Principal::class);

		/** @var IOutput | \PHPUnit_Framework_MockObject_MockObject $output */
		$output = $this->createMock(IOutput::class);

		$repair = new RemoveInvalidShares($db, $principal);
		$this->assertEquals("Remove invalid calendar and addressbook shares", $repair->getName());
		$repair->run($output);

		$query = $db->getQueryBuilder();
		$result = $query->select('*')->from('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter('principal:unknown')))->execute();
		$data = $result->fetchAll();
		$result->closeCursor();
		$this->assertEquals(0, count($data));
	}
}
