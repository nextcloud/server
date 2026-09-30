<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Template;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\Files\FilenameValidator;
use OC\Files\Template\TemplateManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Test\TestCase;

class TemplateManagerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private Coordinator&MockObject $bootstrapCoordinator;
	private TemplateManager $templateManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')
			->willReturnCallback(fn ($string, $params) => sprintf($string, ...$params));
		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->method('get')
			->willReturn($l10n);
		$database = $this->createMock(IDBConnection::class);
		$database->method('supports4ByteText')->willReturn(true);
		$config = $this->createMock(IConfig::class);
		$config->method('getUserValue')->willReturn('');
		$logger = new NullLogger();

		$filenameValidator = new FilenameValidator(
			$l10nFactory,
			$database,
			$config,
			$logger,
		);

		$serverContainer = $this->createMock(ContainerInterface::class);
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->bootstrapCoordinator = $this->createMock(Coordinator::class);
		$this->bootstrapCoordinator->method('getRegistrationContext')
			->willReturn(new RegistrationContext($logger));
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')
			->willReturn($user);
		$userManager = $this->createMock(IUserManager::class);
		$previewManager = $this->createMock(IPreview::class);

		$this->templateManager = new TemplateManager(
			$serverContainer,
			$eventDispatcher,
			$this->bootstrapCoordinator,
			$this->rootFolder,
			$userSession,
			$userManager,
			$previewManager,
			$config,
			$l10nFactory,
			$logger,
			$filenameValidator,
			\OC::$SERVERROOT,
		);
	}

	public function testCreateFromTemplateShoudValidateFilename(): void {
		$this->expectException(GenericFileException::class);

		$fileDirectory = '/';
		$filePath = $fileDirectory . str_repeat('a', 251);

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('get')
			->willReturnCallback(function ($path) use ($filePath, $fileDirectory) {
				if ($path === $filePath) {
					throw new NotFoundException();
				}
				return $this->createMock(Folder::class);
			});
		$userFolder->method('nodeExists')
			->willReturnCallback(function ($path) use ($filePath, $fileDirectory): bool {
				return $path === $fileDirectory;
			});
		$this->rootFolder->method('getUserFolder')
			->willReturn($userFolder);

		$this->templateManager->createFromTemplate($filePath);
	}

	public function testListsContextualTemplatesWithoutPersonalDirectory(): void {
		$root = $this->createMock(Folder::class);
		$root->method('getPath')->willReturn('/user1/files');
		$root->method('getRelativePath')->willReturn('/.Templates/Letter.txt');
		$root->method('isReadable')->willReturn(true);
		$mount = $this->createMock(\OCP\Files\Mount\IMountPoint::class);
		$mount->method('getMountPoint')->willReturn('/user1/files');
		$root->method('getMountPoint')->willReturn($mount);
		$templates = $this->createMock(Folder::class);
		$templates->method('isReadable')->willReturn(true);
		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getId')->willReturn(12);
		$file->method('isReadable')->willReturn(true);
		$file->method('getPath')->willReturn('/user1/files/.Templates/Letter.txt');
		$unreadable = $this->createMock(\OCP\Files\File::class);
		$templates->method('searchByMime')->willReturn([$file, $unreadable]);
		$root->method('get')->willReturnMap([['/', $root], ['.Templates', $templates]]);
		$this->rootFolder->method('getUserFolder')->willReturn($root);
		$type = new \OCP\Files\Template\TemplateFileCreator('files', 'Text', '.txt');
		$type->addMimetype('text/plain')->addMimetype('text');
		$this->templateManager->registerTemplateFileCreator(fn () => $type);
		$list = $this->templateManager->listTemplates('/');
		self::assertCount(1, $list[0]['templates']);
		self::assertSame('/.Templates/Letter.txt', $list[0]['templates'][0]->jsonSerialize()['templateId']);
		self::assertSame([], $this->templateManager->listTemplates()[0]['templates']);
	}


	public function testDoesNotCopyUnreadableTemplate(): void {
		$root = $this->createMock(Folder::class);
		$destination = $this->createMock(Folder::class);
		$template = $this->createMock(\OCP\Files\File::class);
		$root->method('nodeExists')->with('/')->willReturn(true);
		$root->method('get')->willReturnCallback(function ($path) use ($destination, $template) {
			return match ($path) {
				'/' => $destination,
				'/Private.txt' => $template,
				default => throw new NotFoundException(),
			};
		});
		$this->rootFolder->method('getUserFolder')->willReturn($root);
		$destination->expects(self::never())->method('newFile');
		$template->expects(self::never())->method('fopen');
		$this->expectException(GenericFileException::class);
		$this->templateManager->createFromTemplate('/Copy.txt', '/Private.txt');
	}

}
