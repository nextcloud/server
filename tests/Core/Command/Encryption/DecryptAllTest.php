<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\DecryptAll;
use OCP\App\IAppManager;
use OCP\Encryption\IManager;
use OCP\IConfig;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class DecryptAllTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\IConfig */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\Encryption\IManager */
	protected $encryptionManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\App\IAppManager */
	protected $appManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Input\InputInterface */
	protected $consoleInput;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Output\OutputInterface */
	protected $consoleOutput;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Helper\QuestionHelper */
	protected $questionHelper;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OC\Encryption\DecryptAll */
	protected $decryptAll;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$this->encryptionManager = $this->getMockBuilder(IManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->appManager = $this->getMockBuilder(IAppManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
			->disableOriginalConstructor()
			->getMock();
		$this->decryptAll = $this->getMockBuilder(\OC\Encryption\DecryptAll::class)
			->disableOriginalConstructor()->getMock();
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
			$this->encryptionManager,
			$this->appManager,
			$this->config,
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

	/**
	 * @dataProvider dataTestExecute
	 */
	public function testExecute($encryptionEnabled, $continue): void {
		$instance = new DecryptAll(
			$this->encryptionManager,
			$this->appManager,
			$this->config,
			$this->decryptAll,
			$this->questionHelper
		);

		$this->encryptionManager->expects($this->once())
			->method('isEnabled')
			->willReturn($encryptionEnabled);

		$this->consoleInput->expects($this->any())
			->method('getArgument')
			->with('user')
			->willReturn('user1');

		if ($encryptionEnabled) {
			$calls = [
				['core', 'encryption_enabled', 'no'],
				['core', 'encryption_enabled', 'yes'],
			];
			$this->config->expects($this->exactly(2))
				->method('setAppValue')
				->willReturnCallback(function () use (&$calls): void {
					$expected = array_shift($calls);
					$this->assertEquals($expected, func_get_args());
				});
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
		} else {
			$this->config->expects($this->never())->method('setAppValue');
			$this->decryptAll->expects($this->never())->method('decryptAll');
			$this->questionHelper->expects($this->never())->method('ask');
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
			$this->encryptionManager,
			$this->appManager,
			$this->config,
			$this->decryptAll,
			$this->questionHelper
		);

		// make sure that we enable encryption again after a exception was thrown
		$calls = [
			['core', 'encryption_enabled', 'no'],
			['core', 'encryption_enabled', 'yes'],
		];
		$this->config->expects($this->exactly(2))
			->method('setAppValue')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->encryptionManager->expects($this->once())
			->method('isEnabled')
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
