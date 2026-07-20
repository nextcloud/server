<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\Errorlog;
use OC\Log\File;
use OC\Log\LogFactory;
use OC\Log\Syslog;
use OC\Log\Systemdlog;
use OC\SystemConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Test\TestCase;

/**
 * Class LogFactoryTest
 *
 * @package Test\Log
 */
class LogFactoryTest extends TestCase {
	protected ContainerInterface&MockObject $c;
	protected LogFactory $factory;
	protected SystemConfig&MockObject $systemConfig;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->c = $this->createMock(ContainerInterface::class);
		$this->systemConfig = $this->createMock(SystemConfig::class);

		$this->factory = new LogFactory($this->c, $this->systemConfig, \OC::$SERVERROOT);
	}

	public static function fileTypeProvider(): array {
		return [
			[
				'file'
			],
			[
				'nextcloud'
			],
			[
				'owncloud'
			],
			[
				'krzxkyr_default'
			]
		];
	}

	/**
	 * @param string $type
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('fileTypeProvider')]
	public function testFile(string $type): void {
		$datadir = \OC::$SERVERROOT . '/data';
		$defaultLog = $datadir . '/nextcloud.log';

		$this->systemConfig->expects($this->exactly(3))
			->method('getValue')
			->willReturnMap([
				['datadirectory', $datadir, $datadir],
				['logfile', $defaultLog, $defaultLog],
				['logfilemode', 0640, 0640],
			]);

		$log = $this->factory->get($type);
		$this->assertInstanceOf(File::class, $log);
	}

	public static function logFilePathProvider():array {
		return [
			[
				'/dev/null',
				'/dev/null'
			],
			[
				'/xdev/youshallfallback',
				\OC::$SERVERROOT . '/data/nextcloud.log'
			]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('logFilePathProvider')]
	public function testFileCustomPath($path, $expected): void {
		$datadir = \OC::$SERVERROOT . '/data';
		$defaultLog = $datadir . '/nextcloud.log';

		$this->systemConfig->expects($this->exactly(3))
			->method('getValue')
			->willReturnMap([
				['datadirectory', $datadir, $datadir],
				['logfile', $defaultLog, $path],
				['logfilemode', 0640, 0640],
			]);

		$log = $this->factory->get('file');
		$this->assertInstanceOf(File::class, $log);
		$this->assertSame($expected, $log->getLogFilePath());
	}

	public function testErrorLog(): void {
		$log = $this->factory->get('errorlog');
		$this->assertInstanceOf(Errorlog::class, $log);
	}

	public function testSystemLog(): void {
		$this->c->expects($this->once())
			->method('get')
			->with(Syslog::class)
			->willReturn($this->createMock(Syslog::class));

		$log = $this->factory->get('syslog');
		$this->assertInstanceOf(Syslog::class, $log);
	}

	public function testSystemdLog(): void {
		$this->c->expects($this->once())
			->method('get')
			->with(Systemdlog::class)
			->willReturn($this->createMock(Systemdlog::class));

		$log = $this->factory->get('systemd');
		$this->assertInstanceOf(Systemdlog::class, $log);
	}
}
