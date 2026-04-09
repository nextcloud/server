<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Log;

use OC\Core\Command\Log\Manage;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ManageTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$config = $this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->command = new Manage($config);
	}

	public function testChangeBackend(): void {
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['backend', 'syslog']
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('log_type', 'syslog');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testChangeLevel(): void {
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['level', 'debug']
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('loglevel', 0);

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testChangeTimezone(): void {
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['timezone', 'UTC']
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('logtimezone', 'UTC');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}


	public function testValidateBackend(): void {
		$this->expectException(\InvalidArgumentException::class);

		self::invokePrivate($this->command, 'validateBackend', ['notabackend']);
	}


	public function testValidateTimezone(): void {
		$this->expectException(\Exception::class);

		// this might need to be changed when humanity colonises Mars
		self::invokePrivate($this->command, 'validateTimezone', ['Mars/OlympusMons']);
	}

	public static function dataConvertLevelString(): array {
		return [
			['dEbug', 0],
			['inFO', 1],
			['Warning', 2],
			['wArn', 2],
			['error', 3],
			['eRr', 3],
			['fAtAl', 4],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataConvertLevelString')]
	public function testConvertLevelString(string $levelString, int $expectedInt): void {
		$this->assertEquals($expectedInt,
			self::invokePrivate($this->command, 'convertLevelString', [$levelString])
		);
	}


	public function testConvertLevelStringInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		self::invokePrivate($this->command, 'convertLevelString', ['abc']);
	}

	public static function dataConvertLevelNumber(): array {
		return [
			[0, 'Debug'],
			[1, 'Info'],
			[2, 'Warning'],
			[3, 'Error'],
			[4, 'Fatal'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataConvertLevelNumber')]
	public function testConvertLevelNumber(int $levelNum, string $expectedString): void {
		$this->assertEquals($expectedString,
			self::invokePrivate($this->command, 'convertLevelNumber', [$levelNum])
		);
	}


	public function testConvertLevelNumberInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		self::invokePrivate($this->command, 'convertLevelNumber', [11]);
	}

	public function testGetConfiguration(): void {
		$this->config->expects($this->exactly(3))
			->method('getSystemValue')
			->willReturnMap([
				['log_type', 'file', 'log_type_value'],
				['loglevel', 2, 0],
				['logtimezone', 'UTC', 'logtimezone_value'],
			]);

		$calls = [
			['Enabled logging backend: log_type_value'],
			['Log level: Debug (0)'],
			['Log timezone: logtimezone_value'],
		];
		$this->consoleOutput->expects($this->exactly(3))
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$call = array_shift($calls);
				$this->assertStringContainsString($call[0], $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
