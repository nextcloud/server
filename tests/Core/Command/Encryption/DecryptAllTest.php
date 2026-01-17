<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\DecryptAll;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DecryptAllTest extends TestCase {
	private MockObject&IConfig $config;
	private MockObject&IAppConfig $appConfig;
	private MockObject&IAppManager $appManager;
	private MockObject&InputInterface $consoleInput;
	private MockObject&OutputInterface $consoleOutput;
	private MockObject&QuestionHelper $questionHelper;
	private MockObject&\OC\Encryption\DecryptAll $decryptAll;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->questionHelper = $this->createMock(QuestionHelper::class);
		$this->decryptAll = $this->createMock(\OC\Encryption\DecryptAll::class);

		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleInput->expects($this->any())
			->method('isInteractive')
			->willReturn(true);
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();

		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('maintenance', false)
			->willReturn(false);
		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->with('files_trashbin')->willReturn(true);
	}

	public function testMaintenanceAndTrashbin(): void {
		// on construct we enable single-user-mode and disable the trash bin
		// on destruct we disable single-user-mode again and enable the trash bin
		$calls = [
			['maintenance', true],
			['maintenance', false],
		];
		$this->config->expects($this->exactly(2))
			->method('setSystemValue')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->appManager->expects($this->once())
			->method('disableApp')
			->with('files_trashbin');
		$this->appManager->expects($this->once())
			->method('enableApp')
			->with('files_trashbin');

		$instance = new DecryptAll(
			$this->appManager,
			$this->config,
			$this->appConfig,
			$this->decryptAll,
			$this->questionHelper
		);
		$this->invokePrivate($instance, 'forceMaintenanceAndTrashbin');

		$this->assertTrue(
			$this->invokePrivate($instance, 'wasTrashbinEnabled')
		);

		$this->assertFalse(
			$this->invokePrivate($instance, 'wasMaintenanceModeEnabled')
		);
		$this->invokePrivate($instance, 'resetMaintenanceAndTrashbin');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestExecute')]
	public function testExecute($encryptionEnabled, $continue): void {
		$instance = new DecryptAll(
			$this->appManager,
			$this->config,
			$this->appConfig,
			$this->decryptAll,
			$this->questionHelper
		);

		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('core', 'encryption_enabled')
			->willReturn($encryptionEnabled);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->with('user')
			->willReturn('user1');

		if ($encryptionEnabled) {
			$calls = [
				['core', 'encryption_enabled', false, false],
				['core', 'encryption_enabled', true, false],
			];
			$this->appConfig->expects($this->exactly(count($calls)))
				->method('setValueBool')
				->willReturnCallback(function () use (&$calls): bool {
					$expected = array_shift($calls);
					$this->assertEquals($expected, func_get_args());
					return true;
				});
		} else {
			$this->appConfig->expects($this->never())
				->method('setValueBool');
		}
		$this->questionHelper->expects($this->once())
			->method('ask')
			->willReturn($continue);
		if ($continue) {
			$this->decryptAll->expects($this->once())
				->method('decryptAll')
				->with($this->consoleInput, $this->consoleOutput, 'user1');
		} else {
			$this->decryptAll->expects($this->never())->method('decryptAll');
		}

		$this->invokePrivate($instance, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public static function dataTestExecute(): array {
		return [
			[true, true],
			[true, false],
			[false, true],
			[false, false]
		];
	}


	public function testExecuteFailure(): void {
		$this->expectException(\Exception::class);

		$instance = new DecryptAll(
			$this->appManager,
			$this->config,
			$this->appConfig,
			$this->decryptAll,
			$this->questionHelper
		);

		// make sure that we enable encryption again after a exception was thrown
		$calls = [
			['core', 'encryption_enabled', false, false],
			['core', 'encryption_enabled', true, false],
		];
		$this->appConfig->expects($this->exactly(2))
			->method('setValuebool')
			->willReturnCallback(function () use (&$calls): bool {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
				return true;
			});
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('core', 'encryption_enabled')
			->willReturn(true);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->with('user')
			->willReturn('user1');

		$this->questionHelper->expects($this->once())
			->method('ask')
			->willReturn(true);

		$this->decryptAll->expects($this->once())
			->method('decryptAll')
			->with($this->consoleInput, $this->consoleOutput, 'user1')
			->willReturnCallback(function (): void {
				throw new \Exception();
			});

		$this->invokePrivate($instance, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
