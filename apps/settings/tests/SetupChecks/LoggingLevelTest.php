<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\SetupChecks;

use OCA\Settings\SetupChecks\LoggingLevel;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LogLevel;
use Test\TestCase;

class LoggingLevelTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
	}

	public static function dataRun(): array {
		return [
			[ILogger::INFO, SetupResult::SUCCESS],
			[ILogger::WARN, SetupResult::SUCCESS],
			[ILogger::ERROR, SetupResult::SUCCESS],
			[ILogger::FATAL, SetupResult::SUCCESS],

			// Debug is valid but will result in an warning
			[ILogger::DEBUG, SetupResult::WARNING],

			// negative - invalid range
			[-1, SetupResult::ERROR],
			// string value instead of number
			['1', SetupResult::ERROR],
			// random string value
			['error', SetupResult::ERROR],
			// PSR logger value
			[LogLevel::ALERT, SetupResult::ERROR],
			// out of range
			[ILogger::FATAL + 1, SetupResult::ERROR],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataRun')]
	public function testRun(string|int $value, string $expected): void {
		$this->urlGenerator->method('linkToDocs')->willReturn('admin-logging');

		$this->config->expects(self::once())
			->method('getSystemValue')
			->with('loglevel', ILogger::WARN)
			->willReturn($value);

		$check = new LoggingLevel($this->l10n, $this->config, $this->urlGenerator);

		$result = $check->run();
		$this->assertEquals($expected, $result->getSeverity());
	}
}
