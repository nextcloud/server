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
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EncryptAllTest extends TestCase {
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

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\Encryption\IEncryptionModule */
	protected $encryptionModule;

	/** @var EncryptAll */
	protected $command;

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
		$this->encryptionModule = $this->getMockBuilder(IEncryptionModule::class)
			->disableOriginalConstructor()
			->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
			->disableOriginalConstructor()
			->getMock();
		$this->consoleInput = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->consoleInput->expects($this->any())
			->method('isInteractive')
			->willReturn(true);
		$this->consoleOutput = $this->getMockBuilder(OutputInterface::class)->getMock();
	}

	public function testEncryptAll(): void {
		// trash bin needs to be disabled in order to avoid adding dummy files to the users
		// trash bin which gets deleted during the encryption process
		$this->appManager->expects($this->once())->method('disableApp')->with('files_trashbin');

		$instance = new EncryptAll($this->encryptionManager, $this->appManager, $this->config, $this->questionHelper);
		$this->invokePrivate($instance, 'forceMaintenanceAndTrashbin');
		$this->invokePrivate($instance, 'resetMaintenanceAndTrashbin');
	}

	/**
	 * @dataProvider dataTestExecute
	 */
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

	public function dataTestExecute() {
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
