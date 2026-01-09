<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Crypto;

use OC\Files\SetupManager;
use OC\Files\View;
use OCA\Encryption\Crypto\EncryptAll;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use OCA\Encryption\Util;
use OCP\Files\FileInfo;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class EncryptAllTest extends TestCase {

	protected KeyManager&MockObject $keyManager;
	protected Util&MockObject $util;
	protected IUserManager&MockObject $userManager;
	protected Setup&MockObject $setupUser;
	protected View&MockObject $view;
	protected IConfig&MockObject $config;
	protected IMailer&MockObject $mailer;
	protected IL10N&MockObject $l;
	protected IFactory&MockObject $l10nFactory;
	protected \Symfony\Component\Console\Helper\QuestionHelper&MockObject $questionHelper;
	protected \Symfony\Component\Console\Input\InputInterface&MockObject $inputInterface;
	protected \Symfony\Component\Console\Output\OutputInterface&MockObject $outputInterface;
	protected UserInterface&MockObject $userInterface;
	protected ISecureRandom&MockObject $secureRandom;
	protected LoggerInterface&MockObject $logger;
	protected SetupManager&MockObject $setupManager;
	protected IUser&MockObject $user1;
	protected IUser&MockObject $user2;

	protected EncryptAll $encryptAll;

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
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->setupManager = $this->createMock(SetupManager::class);

		/**
		 * We need format method to return a string
		 * @var OutputFormatterInterface&MockObject
		 */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturnArgument(0);

		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($outputFormatter);

		$this->user1 = $this->createMock(IUser::class);
		$this->user1->method('getUID')->willReturn('user1');

		$this->user2 = $this->createMock(IUser::class);
		$this->user2->method('getUID')->willReturn('user2');

		$this->userManager->expects($this->any())->method('getSeenUsers')->will($this->returnCallback(function () {
			yield $this->user1;
			yield $this->user2;
		}));
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
			$this->secureRandom,
			$this->logger,
			$this->setupManager,
		);
	}

	protected function createFileInfoMock($type, string $name): FileInfo&MockObject {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('getType')->willReturn($type);
		$fileInfo->method('getName')->willReturn($name);
		return $fileInfo;
	}

	public function testEncryptAll(): void {
		/** @var EncryptAll&MockObject $encryptAll */
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
					$this->secureRandom,
					$this->logger,
					$this->setupManager,
				]
			)
			->onlyMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);
		$encryptAll->expects($this->once())->method('createKeyPairs')->with();
		$encryptAll->expects($this->once())->method('outputPasswords')->with();
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testEncryptAllWithMasterKey(): void {
		/** @var EncryptAll&MockObject $encryptAll */
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
					$this->secureRandom,
					$this->logger,
					$this->setupManager,
				]
			)
			->onlyMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(true);
		$encryptAll->expects($this->never())->method('createKeyPairs');
		$this->keyManager->expects($this->once())->method('validateMasterKey');
		$encryptAll->expects($this->once())->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->never())->method('outputPasswords');

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);
	}

	public function testCreateKeyPairs(): void {
		/** @var EncryptAll&MockObject $encryptAll */
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
					$this->secureRandom,
					$this->logger,
					$this->setupManager,
				]
			)
			->onlyMethods(['generateOneTimePassword', 'setupUserFileSystem'])
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

		$encryptAll->expects($this->once())->method('setupUserFileSystem')->with($this->user1);
		$encryptAll->expects($this->once())->method('generateOneTimePassword')->with($this->user1)->willReturn('password');
		$this->setupUser->expects($this->once())->method('setupUser')->with('user1', 'password');

		$this->invokePrivate($encryptAll, 'createKeyPairs');

		$userPasswords = $this->invokePrivate($encryptAll, 'userCache');

		// we only expect the skipped user, because generateOneTimePassword which
		// would set the user with the new password was mocked.
		// This method will be tested separately
		$this->assertSame(1, count($userPasswords));
		$this->assertSame('', $userPasswords['user2']['password']);
	}

	public function testEncryptAllUsersFiles(): void {
		/** @var EncryptAll&MockObject $encryptAll */
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
					$this->secureRandom,
					$this->logger,
					$this->setupManager,
				]
			)
			->onlyMethods(['encryptUsersFiles'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);
		$this->invokePrivate($encryptAll, 'userCache', [[
			'user1' => [
				'password' => 'pwd1',
				'user' => $this->user1,
			],
			'user2' => [
				'password' => 'pwd2',
				'user' => $this->user2,
			]
		]]);

		$encryptAllCalls = [];
		$encryptAll->expects($this->exactly(2))
			->method('encryptUsersFiles')
			->willReturnCallback(function ($uid) use (&$encryptAllCalls): void {
				$encryptAllCalls[] = $uid;
			});

		$this->invokePrivate($encryptAll, 'encryptAllUsersFiles');
		self::assertEquals([
			$this->user1,
			$this->user2,
		], $encryptAllCalls);
	}

	public function testEncryptUsersFiles(): void {
		/** @var EncryptAll&MockObject $encryptAll */
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
					$this->secureRandom,
					$this->logger,
					$this->setupManager,
				]
			)
			->onlyMethods(['encryptFile'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		$this->view->expects($this->exactly(2))->method('getDirectoryContent')
			->willReturnMap([
				[
					'/user1/files',
					'',
					null,
					[
						$this->createFileInfoMock(FileInfo::TYPE_FOLDER, 'foo'),
						$this->createFileInfoMock(FileInfo::TYPE_FILE, 'bar'),
					],
				],
				[
					'/user1/files/foo',
					'',
					null,
					[
						$this->createFileInfoMock(FileInfo::TYPE_FILE, 'subfile'),
					],
				],
			]);

		$encryptAllCalls = [];
		$encryptAll->expects($this->exactly(2))
			->method('encryptFile')
			->willReturnCallback(function (FileInfo $file, string $path) use (&$encryptAllCalls): bool {
				$encryptAllCalls[] = $path;
				return true;
			});

		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$this->outputInterface->expects($this->any())
			->method('getFormatter')
			->willReturn($outputFormatter);
		$progressBar = new ProgressBar($this->outputInterface);

		$this->invokePrivate($encryptAll, 'encryptUsersFiles', [$this->user1, $progressBar, '']);
		self::assertEquals([
			'/user1/files/bar',
			'/user1/files/foo/subfile',
		], $encryptAllCalls);
	}

	public function testGenerateOneTimePassword(): void {
		$password = $this->invokePrivate($this->encryptAll, 'generateOneTimePassword', [$this->user1]);
		$this->assertTrue(is_string($password));
		$this->assertSame(8, strlen($password));

		$userPasswords = $this->invokePrivate($this->encryptAll, 'userCache');
		$this->assertSame(1, count($userPasswords));
		$this->assertSame($password, $userPasswords['user1']['password']);
	}

	/**
	 * @param $isEncrypted
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestEncryptFile')]
	public function testEncryptFile($isEncrypted): void {
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())->method('isEncrypted')
			->willReturn($isEncrypted);
		$this->view->expects($this->never())->method('getFileInfo');


		if ($isEncrypted) {
			$this->view->expects($this->never())->method('copy');
			$this->view->expects($this->never())->method('rename');
		} else {
			$this->view->expects($this->once())->method('copy');
			$this->view->expects($this->once())->method('rename');
		}

		$this->assertTrue(
			$this->invokePrivate($this->encryptAll, 'encryptFile', [$fileInfo, 'foo.txt'])
		);
	}

	public static function dataTestEncryptFile(): array {
		return [
			[true],
			[false],
		];
	}
}
