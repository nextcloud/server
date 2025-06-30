<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Log;

use OC\Core\Command\Log\File;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class FileTest extends TestCase {
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

		$this->command = new File($config);
	}

	public function testEnable(): void {
		$this->config->method('getSystemValue')->willReturnArgument(1);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['enable', 'true']
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('log_type', 'file');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testChangeFile(): void {
		$this->config->method('getSystemValue')->willReturnArgument(1);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['file', '/foo/bar/file.log']
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('logfile', '/foo/bar/file.log');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public static function changeRotateSizeProvider(): array {
		return [
			['42', 42],
			['0', 0],
			['1 kB', 1024],
			['5MB', 5 * 1024 * 1024],
		];
	}

	/**
	 * @dataProvider changeRotateSizeProvider
	 */
	public function testChangeRotateSize($optionValue, $configValue): void {
		$this->config->method('getSystemValue')->willReturnArgument(1);
		$this->consoleInput->method('getOption')
			->willReturnMap([
				['rotate-size', $optionValue]
			]);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('log_rotate_size', $configValue);

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testGetConfiguration(): void {
		$this->config->method('getSystemValue')
			->willReturnMap([
				['log_type', 'file', 'log_type_value'],
				['datadirectory', \OC::$SERVERROOT . '/data', '/data/directory/'],
				['logfile', '/data/directory/nextcloud.log', '/var/log/nextcloud.log'],
				['log_rotate_size', 100 * 1024 * 1024, 5 * 1024 * 1024],
			]);

		$calls = [
			['Log backend file: disabled'],
			['Log file: /var/log/nextcloud.log'],
			['Rotate at: 5 MB'],
		];
		$this->consoleOutput->expects($this->exactly(3))
			->method('writeln')
			->willReturnCallback(function (string $message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected[0], $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
