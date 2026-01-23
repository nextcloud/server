<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\Disable;
use OCP\IAppConfig;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DisableTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $appConfig;
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
		$appConfig = $this->appConfig = $this->getMockBuilder(IAppConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var IConfig $config */
		/** @var IAppConfig $appConfig */
		$this->command = new Disable($config, $appConfig);
	}


	public static function dataDisable(): array {
		return [
			['yes', true, 'Encryption disabled'],
			['no', false, 'Encryption is already disabled'],
		];
	}

	/**
	 *
	 * @param string $oldStatus
	 * @param bool $isUpdating
	 * @param string $expectedString
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataDisable')]
	public function testDisable($oldStatus, $isUpdating, $expectedString): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('core', 'encryption_enabled', 'no')
			->willReturn($oldStatus);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains($expectedString));

		if ($isUpdating) {
			$this->appConfig->expects($this->once())
				->method('setValueString')
				->with('core', 'encryption_enabled', 'no');
		}

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
