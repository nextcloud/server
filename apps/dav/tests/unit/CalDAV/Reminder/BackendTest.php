<?php
/**
 * @copyright Copyright (c) 2018, Thomas Citharel
 *
 * @author Thomas Citharel <tcit@tcit.fr>
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
namespace OCA\DAV\Tests\unit\CalDAV\Reminder;

use OCP\IDBConnection;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\AppFramework\Utility\ITimeFactory;
use OCA\DAV\CalDAV\Reminder\Backend as ReminderBackend;
use Test\TestCase;

class BackendTest extends TestCase {

    /**
     * Reminder Backend
     *
     * @var ReminderBackend|\PHPUnit\Framework\MockObject\MockObject
     */
    private $reminderBackend;

    /** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject */
	private $dbConnection;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $timeFactory;

    public function setUp() {
		parent::setUp();

		$this->dbConnection = $this->createMock(IDBConnection::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
        $this->reminderBackend = new ReminderBackend($this->dbConnection, $this->timeFactory);
    }

    public function testCleanRemindersForEvent(): void
    {
		/** @var IQueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));

		$expr->method('eq')
			->will($this->returnValueMap([
				['calendarid', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
				['objecturi', 'createNamedParameter-2', null, 'WHERE_CLAUSE_2'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				[1, \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['object.ics', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('delete')
			->with('calendar_reminders')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(3))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(6))
			->method('andWhere')
			->with('WHERE_CLAUSE_2')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(7))
			->method('execute')
			->with()
			->willReturn($stmt);

		$this->reminderBackend->cleanRemindersForEvent(1, 'object.ics');
	}

	public function testCleanRemindersForCalendar(): void
    {
		/** @var IQueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));

		$expr->method('eq')
			->will($this->returnValueMap([
				['calendarid', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				[1337, \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('delete')
			->with('calendar_reminders')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(3))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('execute')
			->with()
			->willReturn($stmt);

		$this->reminderBackend->cleanRemindersForCalendar(1337);
	}

	public function testRemoveReminder(): void
	{
		/** @var IQueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));

		$expr->method('eq')
			->will($this->returnValueMap([
				['id', 'createNamedParameter-1', null, 'WHERE_CLAUSE_1'],
			]));
		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				[16, \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('delete')
			->with('calendar_reminders')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(3))
			->method('where')
			->with('WHERE_CLAUSE_1')
			->will($this->returnValue($queryBuilder));
		$queryBuilder->expects($this->at(4))
			->method('execute')
			->with()
			->willReturn($stmt);

		$this->reminderBackend->removeReminder(16);
	}

	public function testGetRemindersToProcess(): void
    {
		$dbData = [[
				'cr.id' => 30,
				'cr.calendarid' => 3,
				'cr.objecturi' => 'object.ics',
				'cr.type' => 'EMAIL',
				'cr.notificationdate' => 1337,
				'cr.uid' => 'user1',
				'co.calendardata' => 'BEGIN:VCALENDAR',
				'c.displayname' => 'My Calendar'
			]];

		$this->timeFactory->expects($this->exactly(2))
			->method('getTime')
			->with()
			->willReturn(1337);

		/** @var IQueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->willReturn($queryBuilder);
		$queryBuilder->method('expr')
			->willReturn($expr);

		$expr->method('eq')
			->willReturnMap([
				['cr.calendarid', 'c.id', null, 'EQ_CLAUSE_1'],
				['co.uri', 'cr.objecturi', null, 'EQ_CLAUSE_2'],
			]);
		$expr->method('andX')
			->willReturnMap([
				['EQ_CLAUSE_1', 'EQ_CLAUSE_2', 'ANDX_CLAUSE'],
			]);

		$expr->method('lte')
			->with('cr.notificationdate', 'createNamedParameter-1', null)
			->willReturn('LTE_CLAUSE_1');

		$expr->method('gte')
			->with('cr.eventstartdate', 'createNamedParameter-1', null)
			->willReturn('GTE_CLAUSE_2');

		$queryBuilder->method('createNamedParameter')
			->willReturnMap([
				[1337, \PDO::PARAM_STR, null, 'createNamedParameter-1'],
			]);

		$queryBuilder->expects($this->at(0))
			->method('select')
			->with(['cr.id', 'cr.calendarid', 'cr.objecturi', 'cr.type', 'cr.notificationdate', 'cr.uid', 'co.calendardata', 'c.displayname'])
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(1))
			->method('from')
			->with('calendar_reminders', 'cr')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(4))
			->method('where')
			->with('LTE_CLAUSE_1')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(7))
			->method('andWhere')
			->with('GTE_CLAUSE_2')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(9))
			->method('leftJoin')
			->with('cr', 'calendars', 'c', 'EQ_CLAUSE_1')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(13))
			->method('leftJoin')
			->with('cr', 'calendarobjects', 'co', 'ANDX_CLAUSE')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(14))
			->method('execute')
			->with()
			->willReturn($stmt);

		$stmt->expects($this->once())
			->method('fetchAll')
			->with()
			->willReturn($dbData);

		$actual = $this->reminderBackend->getRemindersToProcess();
		$this->assertEquals($dbData, $actual);
	}

	public function testInsertReminder(): void
    {
		/** @var IQueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder */
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $stmt = $this->createMock(\Doctrine\DBAL\Driver\Statement::class);
		$expr = $this->createMock(\OCP\DB\QueryBuilder\IExpressionBuilder::class);

		$this->dbConnection->expects($this->once())
			->method('getQueryBuilder')
			->with()
			->will($this->returnValue($queryBuilder));
		$queryBuilder->method('expr')
			->will($this->returnValue($expr));

		$queryBuilder->method('createNamedParameter')
			->will($this->returnValueMap([
				['user1', \PDO::PARAM_STR, null, 'createNamedParameter-1'],
				['1', \PDO::PARAM_STR, null, 'createNamedParameter-2'],
				['object.ics', \PDO::PARAM_STR, null, 'createNamedParameter-3'],
				['EMAIL', \PDO::PARAM_STR, null, 'createNamedParameter-4'],
				[1227, \PDO::PARAM_STR, null, 'createNamedParameter-5'],
				[1337, \PDO::PARAM_STR, null, 'createNamedParameter-6'],
			]));

		$queryBuilder->expects($this->at(0))
			->method('insert')
			->with('calendar_reminders')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(7))
			->method('values')
			->with([
				'uid' => 'createNamedParameter-1',
				'calendarid' => 'createNamedParameter-2',
				'objecturi' => 'createNamedParameter-3',
				'type' => 'createNamedParameter-4',
				'notificationdate' => 'createNamedParameter-5',
				'eventstartdate' => 'createNamedParameter-6',
			])
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->at(8))
			->method('execute')
			->with()
			->willReturn($stmt);

		$actual = $this->reminderBackend->insertReminder('user1', '1', 'object.ics', 'EMAIL', 1227, 1337);
    }
}
