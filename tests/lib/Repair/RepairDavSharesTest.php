<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\RepairDavShares;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use function in_array;

class RepairDavSharesTest extends TestCase {

	private IOutput&MockObject $output;
	private IConfig&MockObject $config;
	private IDBConnection&MockObject $dbc;
	private LoggerInterface&MockObject $logger;
	private IGroupManager&MockObject $groupManager;
	private RepairDavShares $repair;

	public function setUp(): void {
		parent::setUp();

		$this->output = $this->createMock(IOutput::class);

		$this->config = $this->createMock(IConfig::class);
		$this->dbc = $this->createMock(IDBConnection::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->repair = new RepairDavShares(
			$this->config,
			$this->dbc,
			$this->groupManager,
			$this->logger
		);
	}

	public function testRun(): void {
		$this->config->expects($this->any())
			->method('getSystemValueString')
			->with('version', '0.0.0')
			->willReturn('20.0.2');

		$this->output->expects($this->atLeastOnce())
			->method('info');

		$existingGroups = [
			'Innocent',
			'Wants Repair',
			'Well förmed',
			'family+friends',
			'family friends',
		];

		$shareResultData = [
			[
				// No update, nothing to escape
				'id' => 0,
				'principaluri' => 'principals/groups/Innocent',
			],
			[
				// Update
				'id' => 1,
				'principaluri' => 'principals/groups/Wants Repair',
			],
			[
				// No update, already proper
				'id' => 2,
				'principaluri' => 'principals/groups/Well+f%C3%B6rmed',
			],
			[
				// No update, unknown group
				'id' => 3,
				'principaluri' => 'principals/groups/Not known',
			],
			[
				// No update, unknown group
				'id' => 4,
				'principaluri' => 'principals/groups/Also%2F%2FNot%23Known',
			],
			[
				// No update, group exists in both forms
				'id' => 5,
				'principaluri' => 'principals/groups/family+friends',
			],
			[
				// No update, already proper
				'id' => 6,
				'principaluri' => 'principals/groups/family%2Bfriends',
			],
			[
				// Update
				'id' => 7,
				'principaluri' => 'principals/groups/family friends',
			],
		];

		$shareResults = $this->createMock(IResult::class);
		$shareResults->expects($this->any())
			->method('fetch')
			->willReturnCallback(function () use (&$shareResultData) {
				return array_pop($shareResultData);
			});

		$expressionBuilder = $this->createMock(IExpressionBuilder::class);

		$selectMock = $this->createMock(IQueryBuilder::class);
		$selectMock->expects($this->any())
			->method('expr')
			->willReturn($expressionBuilder);
		$selectMock->expects($this->once())
			->method('select')
			->willReturnSelf();
		$selectMock->expects($this->once())
			->method('from')
			->willReturnSelf();
		$selectMock->expects($this->once())
			->method('where')
			->willReturnSelf();
		$selectMock->expects($this->once())
			->method('execute')
			->willReturn($shareResults);

		$updateCalls = [];
		$updateMock = $this->createMock(IQueryBuilder::class);
		$updateMock->expects($this->any())
			->method('expr')
			->willReturn($expressionBuilder);
		$updateMock->expects($this->once())
			->method('update')
			->willReturnSelf();
		$updateMock->expects($this->any())
			->method('set')
			->willReturnSelf();
		$updateMock->expects($this->once())
			->method('where')
			->willReturnSelf();
		$updateMock->expects($this->exactly(4))
			->method('setParameter')
			->willReturnCallback(function () use (&$updateCalls, &$updateMock) {
				$updateCalls[] = func_get_args();
				return $updateMock;
			});
		$updateMock->expects($this->exactly(2))
			->method('execute');

		$this->dbc->expects($this->atLeast(2))
			->method('getQueryBuilder')
			->willReturnOnConsecutiveCalls($selectMock, $updateMock);

		$this->groupManager->expects($this->any())
			->method('groupExists')
			->willReturnCallback(function (string $gid) use ($existingGroups) {
				return in_array($gid, $existingGroups);
			});

		$this->repair->run($this->output);
		self::assertEquals([
			['updatedPrincipalUri', 'principals/groups/' . urlencode('family friends'), null],
			['shareId', 7, null],
			['updatedPrincipalUri', 'principals/groups/' . urlencode('Wants Repair'), null],
			['shareId', 1, null]
		], $updateCalls);
	}
}
