<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\Disable;
use OCP\IAppConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DisableTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $appConfig;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$appConfig = $this->appConfig = $this->getMockBuilder(IAppConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var IAppConfig $appConfig */
		$this->command = new Disable($appConfig);
	}


	public static function dataDisable(): array {
		return [
			[true, true, 'Encryption disabled'],
			[false, false, 'Encryption is already disabled'],
		];
	}

	/**
	 *
	 * @param bool $oldStatus
	 * @param bool $isUpdating
	 * @param string $expectedString
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataDisable')]
	public function testDisable(bool $oldStatus, bool $isUpdating, string $expectedString): void {
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('core', 'encryption_enabled', false)
			->willReturn($oldStatus);

		$this->consoleOutput->expects($this->once())
			->method('writeln')
			->with($this->stringContains($expectedString));

		if ($isUpdating) {
			$this->appConfig->expects($this->once())
				->method('setValueBool')
				->with('core', 'encryption_enabled', false);
		}

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
