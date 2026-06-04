<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Snowflake;

use OC\AppFramework\Utility\TimeFactory;
use OC\Snowflake\ISequence;
use OC\Snowflake\SnowflakeDecoder;
use OC\Snowflake\SnowflakeGenerator;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IServerInfo;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @package Test
 */
class GeneratorTest extends TestCase {
	private SnowflakeDecoder $decoder;
	private ISequence&MockObject $sequence;
	private IServerInfo $serverInfo;

	#[\Override]
	public function setUp(): void {
		$this->decoder = new SnowflakeDecoder();

		$this->sequence = $this->createMock(ISequence::class);
		$this->sequence->method('isAvailable')->willReturn(true);
		$this->sequence->method('nextId')->willReturn(421);

		$this->serverInfo = Server::get(IServerInfo::class);
	}

	public function testGenerator(): void {
		$generator = new SnowflakeGenerator(new TimeFactory(), $this->sequence, $this->serverInfo);
		$snowflakeId = $generator->nextId();
		$data = $this->decoder->decode($generator->nextId());

		$this->assertIsString($snowflakeId);
		// Check timestamp
		$this->assertGreaterThan(time() - 30, $data->getCreatedAt()->format('U'));

		// Check serverId
		$this->assertGreaterThanOrEqual(0, $data->getServerId());
		$this->assertLessThanOrEqual(511, $data->getServerId());

		// Check sequenceId
		$this->assertGreaterThanOrEqual(0, $data->getSequenceId());
		$this->assertLessThanOrEqual(4095, $data->getSequenceId());

		// Check CLI
		$this->assertTrue($data->isCli());

		// Check serverId
		$this->assertEquals($this->serverInfo->getServerId(), $data->getServerId());
	}

	public function testMinForTime(): void {
		$generator = new SnowflakeGenerator(new TimeFactory(), $this->sequence, $this->serverInfo);
		$now = time();
		$snowflakeId = $generator->minForTimeId($now);
		$data = $this->decoder->decode($snowflakeId);

		$this->assertIsString($snowflakeId);

		// Check timestamp
		$this->assertEquals($now - ISnowflakeGenerator::TS_OFFSET, $data->getSeconds());

		// Check all other fields are at zero
		$this->assertEquals(0, $data->getMilliseconds());
		$this->assertEquals(0, $data->getServerId());
		$this->assertEquals(0, $data->getSequenceId());
		$this->assertFalse($data->isCli());
		$this->assertEquals(0, $data->getServerId());
	}

	#[DataProvider('provideSnowflakeData')]
	public function testGeneratorWithFixedTime(string $date, int $expectedSeconds, int $expectedMilliseconds): void {
		$dt = new \DateTimeImmutable($date);
		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('now')->willReturn($dt);

		$generator = new SnowflakeGenerator($timeFactory, $this->sequence, $this->serverInfo);
		$data = $this->decoder->decode($generator->nextId());

		$this->assertEquals($expectedSeconds, $data->getCreatedAt()->format('U') - ISnowflakeGenerator::TS_OFFSET);
		$this->assertEquals($expectedMilliseconds, (int)$data->getCreatedAt()->format('v'));
		$this->assertEquals($this->serverInfo->getServerId(), $data->getServerId());
	}

	public static function provideSnowflakeData(): array {
		$tests = [
			['2025-10-01 00:00:00.000000', 0, 0],
			['2025-10-01 00:00:01.000000', 1, 0],
			['2025-10-01 00:00:00.001000', 0, 1],
			['2027-08-06 03:08:30.000975', 58244910, 0],
			['2030-06-21 12:59:33.100875', 149000373, 100],
			['2038-01-18 13:33:37.666666', 388157617, 666],
		];
		// Timestamp in 32 bits can't go after 2038. Add few cases for 64 bits.
		if (PHP_INT_SIZE === 8) {
			$tests[] = ['2039-12-31 23:59:59.999999', 449711999, 999];
			$tests[] = ['2086-06-21 12:59:33.010875', 1916225973, 10];
		}

		return $tests;
	}
}
