<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OC\BackgroundJob\JobClassesRegistry;
use OC\BackgroundJob\JobRuns;
use OCP\BackgroundJob\JobRun;
use OCP\BackgroundJob\JobStatus;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\ISnowflakeDecoder;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;
use Test\TestCase;

/**
 * @package Test\BackgroundJob
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class JobRunsTest extends TestCase {
	private IDBConnection $connection;
	private JobClassesRegistry $registry;
	private JobRuns $runs;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->registry = Server::get(JobClassesRegistry::class);
		$this->runs = new JobRuns(
			$this->connection,
			Server::get(ISnowflakeGenerator::class),
			Server::get(ISnowflakeDecoder::class),
			$this->registry,
		);
	}

	public function testJobStarted(): void {
		$myPid = 1337;
		$myClass = DummyJob::class;

		$runId = $this->runs->started($this->registry->getId(DummyJob::class), $myPid);

		$this->assertGreaterThan(0, $runId);
	}

	public function testJobSucceeded(): void {
		$myPid = 1337;
		$myClass = DummyJob::class;

		$runId = $this->runs->started($this->registry->getId(DummyJob::class), $myPid);

		$result = $this->runs->finished($runId, 12, 9876543);

		$this->assertTrue($result);
	}

	public function testJobFailed(): void {
		$myPid = 1337;
		$myClass = DummyJob::class;

		$runId = $this->runs->started($this->registry->getId(DummyJob::class), $myPid);

		$result = $this->runs->finished($runId, 13, 87654321, JobStatus::FAILED);

		$this->assertTrue($result);
	}

	public function testRunningJobs(): void {
		$myPid = 1337;
		$myClass = DummyJob::class;

		$runId = $this->runs->started($this->registry->getId(DummyJob::class), $myPid);

		$runningJobs = 0;
		foreach ($this->runs->runningJobs() as $job) {
			$this->assertInstanceOf(JobRun::class, $job);
			++$runningJobs;
		}
		$this->assertGreaterThan(0, $runningJobs);
	}
}
