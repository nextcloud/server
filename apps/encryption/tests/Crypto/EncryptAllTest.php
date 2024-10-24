<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Crypto;

use OC\Files\View;
use OCA\Encryption\Crypto\EncryptAll;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\UserInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EncryptAllTest extends TestCase {

	/** @var \PHPUnit\Framework\MockObject\MockObject|KeyManager */
	protected $keyManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|Util */
	protected $util;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IUserManager */
	protected $userManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|Setup */
	protected $setupUser;

	/** @var \PHPUnit\Framework\MockObject\MockObject|View */
	protected $view;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IConfig */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IMailer */
	protected $mailer;

	/** @var \PHPUnit\Framework\MockObject\MockObject|IL10N */
	protected $l;

	/** @var \PHPUnit\Framework\MockObject\MockObject | IFactory */
	protected $l10nFactory;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Helper\QuestionHelper */
	protected $questionHelper;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Input\InputInterface */
	protected $inputInterface;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Output\OutputInterface */
	protected $outputInterface;

	/** @var \PHPUnit\Framework\MockObject\MockObject|UserInterface */
	protected $userInterface;

	/** @var \PHPUnit\Framework\MockObject\MockObject|ISecureRandom */
	protected $secureRandom;

	/** @var EncryptAll */
	protected $encryptAll;

	protected function setUp(): void {
		parent::setUp();
		$this->setupUser = $this->getMockBuilder(Setup::class)
			->disableOriginalConstructor()->getMock();
		$this->keyManager = $this->getMockBuilder(KeyManager::class)
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();
		$this->view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder(IMailer::class)
			->disableOriginalConstructor()->getMock();
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)
			->disableOriginalConstructor()->getMock();
		$this->inputInterface = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->outputInterface = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->userInterface = $this->getMockBuilder(UserInterface::class)
			->disableOriginalConstructor()->getMock();

		/**
		 * We need format method to return a string
		 * @var OutputFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
		 */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturnArgument(0);

		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($outputFormatter);

		$this->userManager->expects($this->any())->method('getBackends')->willReturn([$this->userInterface]);
		$this->userInterface->expects($this->any())->method('getUsers')->willReturn(['user1', 'user2']);

		$this->secureRandom = $this->getMockBuilder(ISecureRandom::class)->disableOriginalConstructor()->getMock();
		$this->secureRandom->expects($this->any())->method('generate')->willReturn('12345678');


		$this->encryptAll = new EncryptAll(
			$this->setupUser,
			$this->userManager,
			$this->view,
			$this->keyManager,
			$this->util,
			$this->config,
			$this->mailer,
			$this->l,
			$this->l10nFactory,
			$this->questionHelper,
			$this->secureRandom
		);
	}

	public function testEncryptAll(): void {
		/** @var EncryptAll | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->l10nFactory,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);
		$encryptAll->expects($this->once())->method('createKeyPairs')->with();
		$encryptAll->expects($this->once())->method('outputPasswords')->with();
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testEncryptAllWithMasterKey(): void {
		/** @var EncryptAll | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->l10nFactory,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(true);
		$encryptAll->expects($this->never())->method('createKeyPairs');
		$this->keyManager->expects($this->once())->method('validateMasterKey');
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->never())->method('outputPasswords');

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testCreateKeyPairs(): void {
		/** @var EncryptAll | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->l10nFactory,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['setupUserFS', 'generateOneTimePassword'])
			->getMock();


		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);

		$this->keyManager->expects($this->exactly(2))->method('userHasKeys')
			->willReturnCallback(
				function ($user) {
					if ($user === 'user1') {
						return false;
					}
					return true;
				}
			);

		$encryptAll->expects($this->once())->method('setupUserFS')->with('user1');
		$encryptAll->expects($this->once())->method('generateOneTimePassword')->with('user1')->willReturn('password');
		$this->setupUser->expects($this->once())->method('setupUser')->with('user1', 'password');

		$this->invokePrivate($encryptAll, 'createKeyPairs');

		$userPasswords = $this->invokePrivate($encryptAll, 'userPasswords');

		// we only expect the skipped user, because generateOneTimePassword which
		// would set the user with the new password was mocked.
		// This method will be tested separately
		$this->assertSame(1, count($userPasswords));
		$this->assertSame('', $userPasswords['user2']);
	}

	public function testEncryptAllUsersFiles(): void {
		/** @var EncryptAll | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->l10nFactory,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptUsersFiles'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);
		$this->invokePrivate($encryptAll, 'userPasswords', [['user1' => 'pwd1', 'user2' => 'pwd2']]);

		$encryptAll->expects($this->exactly(2))->method('encryptUsersFiles')
			->withConsecutive(
				['user1'],
				['user2'],
			);

		$this->invokePrivate($encryptAll, 'encryptAllUsersFiles');
	}

	public function testEncryptUsersFiles(): void {
		/** @var EncryptAll | \PHPUnit\Framework\MockObject\MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder(EncryptAll::class)
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->l10nFactory,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptFile', 'setupUserFS'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		$this->view->expects($this->exactly(2))->method('getDirectoryContent')
			->withConsecutive(
				['/user1/files'],
				['/user1/files/foo'],
			)->willReturnOnConsecutiveCalls(
				[
					['name' => 'foo', 'type' => 'dir'],
					['name' => 'bar', 'type' => 'file'],
				],
				[
					['name' => 'subfile', 'type' => 'file']
				]
			);

		$this->view->expects($this->any())->method('is_dir')
			->willReturnCallback(
				function ($path) {
					if ($path === '/user1/files/foo') {
						return true;
					}
					return false;
				}
			);

		$encryptAll->expects($this->exactly(2))->method('encryptFile')
			->withConsecutive(
				['/user1/files/bar'],
				['/user1/files/foo/subfile'],
			);

		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$this->outputInterface->expects($this->any())
			->method('getFormatter')
			->willReturn($outputFormatter);
		$progressBar = new ProgressBar($this->outputInterface);

		$this->invokePrivate($encryptAll, 'encryptUsersFiles', ['user1', $progressBar, '']);
	}

	public function testGenerateOneTimePassword(): void {
		$password = $this->invokePrivate($this->encryptAll, 'generateOneTimePassword', ['user1']);
		$this->assertTrue(is_string($password));
		$this->assertSame(8, strlen($password));

		$userPasswords = $this->invokePrivate($this->encryptAll, 'userPasswords');
		$this->assertSame(1, count($userPasswords));
		$this->assertSame($password, $userPasswords['user1']);
	}

	/**
	 * @dataProvider dataTestEncryptFile
	 * @param $isEncrypted
	 */
	public function testEncryptFile($isEncrypted): void {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())->method('isEncrypted')
			->willReturn($isEncrypted);
		$this->view->expects($this->any())->method('getFileInfo')
			->willReturn($fileInfo);


		if ($isEncrypted) {
			$this->view->expects($this->never())->method('copy');
			$this->view->expects($this->never())->method('rename');
		} else {
			$this->view->expects($this->once())->method('copy');
			$this->view->expects($this->once())->method('rename');
		}

		$this->assertTrue(
			$this->invokePrivate($this->encryptAll, 'encryptFile', ['foo.txt'])
		);
	}

	public function dataTestEncryptFile() {
		return [
			[true],
			[false],
		];
	}
}
