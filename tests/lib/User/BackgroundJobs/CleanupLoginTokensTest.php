<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User\BackgroundJobs;

use OC\Authentication\RememberLogin\RememberLoginTokenMapper;
use OC\User\BackgroundJobs\CleanupLoginTokens;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanupLoginTokensTest extends TestCase {
	private const NOW = 1790000000;
	private const MAX_AGE = 60 * 60 * 24 * 15;

	private ITimeFactory&MockObject $timeFactory;
	private IDBConnection&MockObject $connection;
	private RememberLoginTokenMapper&MockObject $rememberLoginTokenMapper;
	private IConfig&MockObject $config;
	private CleanupLoginTokens $job;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(self::NOW);
		$this->connection = $this->createMock(IDBConnection::class);
		$this->rememberLoginTokenMapper = $this->createMock(RememberLoginTokenMapper::class);
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValueInt')
			->with('remember_login_cookie_lifetime', self::MAX_AGE)
			->willReturn(self::MAX_AGE);

		$this->job = new CleanupLoginTokens(
			$this->timeFactory,
			$this->connection,
			$this->rememberLoginTokenMapper,
			$this->config,
		);
	}

	public function testRun(): void {
		$cutoff = self::NOW - self::MAX_AGE;

		$this->rememberLoginTokenMapper->expects($this->once())
			->method('deleteOlderThan')
			->with($cutoff);

		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('expr')->willReturn($this->createMock(IExpressionBuilder::class));
		$qb->expects($this->once())
			->method('delete')
			->with('preferences')
			->willReturnSelf();
		$qb->method('where')->willReturnSelf();
		$qb->method('andWhere')->willReturnSelf();
		$qb->expects($this->once())
			->method('createNamedParameter')
			->with($cutoff);
		$qb->expects($this->once())
			->method('executeStatement');
		$this->connection->method('getQueryBuilder')->willReturn($qb);

		self::invokePrivate($this->job, 'run', [null]);
	}
}
