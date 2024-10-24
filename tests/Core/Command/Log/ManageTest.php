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

	public function convertLevelStringProvider() {
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

	/**
	 * @dataProvider convertLevelStringProvider
	 */
	public function testConvertLevelString($levelString, $expectedInt): void {
		$this->assertEquals($expectedInt,
			self::invokePrivate($this->command, 'convertLevelString', [$levelString])
		);
	}


	public function testConvertLevelStringInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		self::invokePrivate($this->command, 'convertLevelString', ['abc']);
	}

	public function convertLevelNumberProvider() {
		return [
			[0, 'Debug'],
			[1, 'Info'],
			[2, 'Warning'],
			[3, 'Error'],
			[4, 'Fatal'],
		];
	}

	/**
	 * @dataProvider convertLevelNumberProvider
	 */
	public function testConvertLevelNumber($levelNum, $expectedString): void {
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
			->withConsecutive(
				['log_type', 'file'],
				['loglevel', 2],
				['logtimezone', 'UTC'],
			)->willReturnOnConsecutiveCalls(
				'log_type_value',
				0,
				'logtimezone_value'
			);

		$this->consoleOutput->expects($this->exactly(3))
			->method('writeln')
			->withConsecutive(
				['Enabled logging backend: log_type_value'],
				['Log level: Debug (0)'],
				['Log timezone: logtimezone_value'],
			);

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
