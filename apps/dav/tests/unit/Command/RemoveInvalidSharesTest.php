<?php
/**
 * @copyright Copyright (c) 2018, ownCloud GmbH
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\Unit\Command;

use OC;
use OCA\DAV\Command\RemoveInvalidShares;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class RemoveInvalidSharesTest
 *
 * @package OCA\DAV\Tests\Unit\Repair
 * @group DB
 */
class RemoveInvalidSharesTest extends TestCase {
	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function setUp(): void {
		parent::setUp();
		$db = OC::$server->get(IDBConnection::class);

		$db->insertIfNotExist('*PREFIX*dav_shares', [
			'principaluri' => 'principal:unknown',
			'type' => 'calendar',
			'access' => 2,
			'resourceid' => 666,
		]);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function test() {
		$db = OC::$server->get(IDBConnection::class);
		/** @var Principal | MockObject $principal */
		$principal = $this->createMock(Principal::class);

		/** @var IOutput | MockObject $output */
		$this->createMock(IOutput::class);

		$repair = new RemoveInvalidShares($db, $principal);
		$this->invokePrivate($repair, 'run', [$this->createMock(InputInterface::class), $this->createMock(OutputInterface::class)]);

		$query = $db->getQueryBuilder();
		$result = $query->select('*')->from('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter('principal:unknown')))->execute();
		$data = $result->fetchAll();
		$result->closeCursor();
		$this->assertCount(0, $data);
	}
}
