<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\Enable;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EnableTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $manager;
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
		$manager = $this->manager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		/** @var \OCP\IConfig $config */
		/** @var \OCP\Encryption\IManager $manager */
		$this->command = new Enable($config, $manager);
	}


	public static function dataEnable(): array {
		return [
			['no', '', [], true, 'Encryption enabled', 'No encryption module is loaded'],
			['yes', '', [], false, 'Encryption is already enabled', 'No encryption module is loaded'],
			['no', '', ['OC_TEST_MODULE' => []], true, 'Encryption enabled', 'No default module is set'],
			['no', 'OC_NO_MODULE', ['OC_TEST_MODULE' => []], true, 'Encryption enabled', 'The current default module does not exist: OC_NO_MODULE'],
			['no', 'OC_TEST_MODULE', ['OC_TEST_MODULE' => []], true, 'Encryption enabled', 'Default module: OC_TEST_MODULE'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataEnable')]
	public function testEnable(string $oldStatus, ?string $defaultModule, array $availableModules, bool $isUpdating, string $expectedString, string $expectedDefaultModuleString): void {
		if ($isUpdating) {
			$this->config->expects($this->once())
				->method('setAppValue')
				->with('core', 'encryption_enabled', 'yes');
		}

		$this->manager->expects($this->atLeastOnce())
			->method('getEncryptionModules')
			->willReturn($availableModules);

		if (empty($availableModules)) {
			$this->config->expects($this->once())
				->method('getAppValue')
				->willReturnMap([
					['core', 'encryption_enabled', 'no', $oldStatus],
				]);
		} else {
			$this->config->expects($this->exactly(2))
				->method('getAppValue')
				->willReturnMap([
					['core', 'encryption_enabled', 'no', $oldStatus],
					['core', 'default_encryption_module', '', $defaultModule],
				]);
		}

		$calls = [
			[$expectedString, 0],
			['', 0],
			[$expectedDefaultModuleString, 0],
		];
		$this->consoleOutput->expects($this->exactly(3))
			->method('writeln')
			->willReturnCallback(function (string $message, int $level) use (&$calls): void {
				$call = array_shift($calls);
				$this->assertStringContainsString($call[0], $message);
				$this->assertSame($call[1], $level);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
