<?php
declare(strict_types=1);
/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\CleanupInvitationTokenJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Test\TestCase;

class CleanupInvitationTokenJobTest extends TestCase {

	/** @var IDBConnection | \PHPUnit_Framework_MockObject_MockObject */
	private $dbConnection;

	/** @var ITimeFactory | \PHPUnit_Framework_MockObject_MockObject */
	private $timeFactory;

	/** @var \OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob */
	private $backgroundJob;

	protected function setUp() {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->backgroundJob = new CleanupInvitationTokenJob(
			$this->dbConnection, $this->timeFactory);
	}

	public function testRun() {
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->with()
			->will($this->returnValue(1337));

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);
		$stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				[1337, \PDO::PARAM_STR, null, 'namedParameter1337']
			]));

		$expr->expects($this->once())
			->method('lt')
			->with('expiration', 'namedParameter1337')
			->will($this->returnValue('LT STATEMENT'));

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));

		$queryBuilder->expects($this->at(0))
			->method('delete')
			->with('calendar_invitation_tokens')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(3))
			->method('where')
			->with('LT STATEMENT')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('execute')
			->with()
			->will($this->returnValue($stmt));

		$this->backgroundJob->run([]);
	}
}