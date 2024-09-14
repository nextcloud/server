<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log;
use OC\Log\PsrLoggerAdapter;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use Test\TestCase;

class PsrLoggerAdapterTest extends TestCase {
	protected Log&MockObject $logger;
	protected PsrLoggerAdapter $loggerAdapter;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(Log::class);
		$this->loggerAdapter = new PsrLoggerAdapter($this->logger);
	}

	/**
	 * @dataProvider dataPsrLoggingLevels
	 */
	public function testLoggingWithPsrLogLevels(string $level, int $expectedLevel): void {
		$this->logger->expects(self::once())
			->method('log')
			->with($expectedLevel, 'test message', ['app' => 'test']);
		$this->loggerAdapter->log($level, 'test message', ['app' => 'test']);
	}

	/**
	 * @dataProvider dataPsrLoggingLevels
	 */
	public function testLogLevelToInt(string $level, int $expectedLevel): void {
		$this->assertEquals($expectedLevel, PsrLoggerAdapter::logLevelToInt($level));
	}

	public function dataPsrLoggingLevels(): array {
		return [
			[LogLevel::ALERT, ILogger::ERROR],
			[LogLevel::CRITICAL, ILogger::ERROR],
			[LogLevel::DEBUG, ILogger::DEBUG],
			[LogLevel::EMERGENCY, ILogger::FATAL],
			[LogLevel::ERROR, ILogger::ERROR],
			[LogLevel::INFO, ILogger::INFO],
			[LogLevel::NOTICE, ILogger::INFO],
			[LogLevel::WARNING, ILogger::WARN],
		];
	}

	/**
	 * @dataProvider dataInvalidLoggingLevel
	 */
	public function testInvalidLoggingLevel($level): void {
		$this->logger->expects(self::never())
			->method('log');
		$this->expectException(InvalidArgumentException::class);

		$this->loggerAdapter->log($level, 'valid message');
	}

	public function dataInvalidLoggingLevel(): array {
		return [
			// invalid string
			['this is not a level'],
			// int out of range
			[ILogger::DEBUG - 1],
			[ILogger::FATAL + 1],
			// float is not allowed
			[1.2345],
			// boolean is not a level
			[true],
			[false],
			//
			[null],
		];
	}
}
