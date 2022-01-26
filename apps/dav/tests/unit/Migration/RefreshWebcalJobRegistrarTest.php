<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit\DAV\Migration;

use Exception;
use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\Migration\RefreshWebcalJobRegistrar;
use OCP\BackgroundJob\IJobList;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RefreshWebcalJobRegistrarTest extends TestCase {

	/** @var IDBConnection | MockObject */
	private $db;

	/** @var IJobList | MockObject */
	private $jobList;

	/** @var RefreshWebcalJobRegistrar */
	private $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(IDBConnection::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->migration = new RefreshWebcalJobRegistrar($this->db, $this->jobList);
	}

	public function testGetName() {
		$this->assertEquals('Registering background jobs to update cache for webcal calendars', $this->migration->getName());
	}

	/**
	 * @throws Exception
	 */
	public function testRun() {
		$output = $this->createMock(IOutput::class);

		$queryBuilder = $this->createMock(IQueryBuilder::class);
		$statement = $this->createMock(IResult::class);

		$this->db->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($queryBuilder);

		$queryBuilder->expects($this->once())
			->method('select')
			->with(['principaluri', 'uri'])
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('from')
			->with('calendarsubscriptions')
			->willReturn($queryBuilder);
		$queryBuilder->expects($this->once())
			->method('executeQuery')
			->willReturn($statement);

		$statement->expects($this->exactly(4))
			->method('fetch')
			->with(PDO::FETCH_ASSOC)
			->willReturnOnConsecutiveCalls(
				[
					'principaluri' => 'foo1',
					'uri' => 'bar1',
				],
				[
					'principaluri' => 'foo2',
					'uri' => 'bar2',
				],
				[
					'principaluri' => 'foo3',
					'uri' => 'bar3',
				],
				null
			);

		$this->jobList->expects($this->exactly(3))
			->method('has')
			->withConsecutive(
				[
					RefreshWebcalJob::class, [
						'principaluri' => 'foo1',
						'uri' => 'bar1',
					]
				],
				[
					RefreshWebcalJob::class,
					[
						'principaluri' => 'foo2',
						'uri' => 'bar2',
					]
				],
				[
					RefreshWebcalJob::class,
					[
						'principaluri' => 'foo3',
						'uri' => 'bar3',
					]
				]
			)
			->willReturnOnConsecutiveCalls(false, true, false);
		$this->jobList->expects($this->exactly(2))
			->method('add')
			->withConsecutive(
				[
					RefreshWebcalJob::class, [
						'principaluri' => 'foo1',
						'uri' => 'bar1',
					]
				],
				[
					RefreshWebcalJob::class,
					[
						'principaluri' => 'foo3',
						'uri' => 'bar3',
					]
				]
			);

		$output->expects($this->once())
			->method('info')
			->with('Added 2 background jobs to update webcal calendars');

		$this->migration->run($output);
	}
}
