<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\EncryptAll;
use OCP\App\IAppManager;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EncryptAllTest extends TestCase {
	private IConfig&MockObject $config;
	private IManager&MockObject $encryptionManager;
	private IAppManager&MockObject $appManager;
	private InputInterface&MockObject $consoleInput;
	private OutputInterface&MockObject $consoleOutput;
	private QuestionHelper&MockObject $questionHelper;
	private IEncryptionModule&MockObject $encryptionModule;

	private EncryptAll $command;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->encryptionManager = $this->createMock(IManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->encryptionModule = $this->createMock(IEncryptionModule::class);
		$this->questionHelper = $this->createMock(QuestionHelper::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleInput->expects($this->any())
			->method('isInteractive')
			->willReturn(true);
		$this->consoleOutput = $this->createMock(OutputInterface::class);
	}

	public function testEncryptAll(): void {
		// trash bin needs to be disabled in order to avoid adding dummy files to the users
		// trash bin which gets deleted during the encryption process
		$this->appManager->expects($this->once())->method('disableApp')->with('files_trashbin');

		$instance = new EncryptAll($this->encryptionManager, $this->appManager, $this->config, $this->questionHelper);
		$this->invokePrivate($instance, 'forceMaintenanceAndTrashbin');
		$this->invokePrivate($instance, 'resetMaintenanceAndTrashbin');
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestExecute')]
	public function testExecute($answer, $askResult): void {
		$command = new EncryptAll($this->encryptionManager, $this->appManager, $this->config, $this->questionHelper);

		$this->encryptionManager->expects($this->once())->method('isEnabled')->willReturn(true);
		$this->questionHelper->expects($this->once())->method('ask')->willReturn($askResult);

		if ($answer === 'Y' || $answer === 'y') {
			$this->encryptionManager->expects($this->once())
				->method('getEncryptionModule')->willReturn($this->encryptionModule);
			$this->encryptionModule->expects($this->once())
				->method('encryptAll')->with($this->consoleInput, $this->consoleOutput);
		} else {
			$this->encryptionManager->expects($this->never())->method('getEncryptionModule');
			$this->encryptionModule->expects($this->never())->method('encryptAll');
		}

		$this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public static function dataTestExecute(): array {
		return [
			['y', true], ['Y', true], ['n', false], ['N', false], ['', false]
		];
	}


	public function testExecuteException(): void {
		$this->expectException(\Exception::class);

		$command = new EncryptAll($this->encryptionManager, $this->appManager, $this->config, $this->questionHelper);
		$this->encryptionManager->expects($this->once())->method('isEnabled')->willReturn(false);
		$this->encryptionManager->expects($this->never())->method('getEncryptionModule');
		$this->encryptionModule->expects($this->never())->method('encryptAll');
		$this->invokePrivate($command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
