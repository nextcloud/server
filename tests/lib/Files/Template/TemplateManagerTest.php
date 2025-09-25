<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Files\Template;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\Files\FilenameValidator;
use OC\Files\Template\TemplateManager;
use OCP\Files\Folder;
use OCP\Files\GenericFileException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IPreview;
use OCP\L10N\IFactory;
use Psr\Log\NullLogger;
use Test\TestCase;

class TemplateManagerTest extends TestCase {

	private IRootFolder $rootFolder;
	private Coordinator $bootstrapCoordinator;

	private TemplateManager $templateManager;

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
		$logger = new NullLogger();

		$filenameValidator = new FilenameValidator(
			$l10nFactory,
			$database,
			$config,
			$logger,
		);

		$serverContainer = $this->createMock(\OCP\IServerContainer::class);
		$eventDispatcher = $this->createMock(\OCP\EventDispatcher\IEventDispatcher::class);
		$this->bootstrapCoordinator = $this->createMock(Coordinator::class);
		$this->bootstrapCoordinator->method('getRegistrationContext')
			->willReturn(new RegistrationContext($logger));
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$userSession = $this->createMock(\OCP\IUserSession::class);
		$userManager = $this->createMock(\OCP\IUserManager::class);
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
			$filenameValidator
		);
	}

	public function testCreateFromTemplateShoudValidateFilename(): void {
		$this->expectException(GenericFileException::class);

		$fileDirectory = '/';
		$filePath = $fileDirectory . str_repeat('a', 251);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('get')
			->willReturnCallback(function ($path) use ($filePath, $fileDirectory) {
				if ($path === $filePath) {
					throw new NotFoundException();
				}
				return $this->createMock(Folder::class);
			});
		$userFolder->method('nodeExists')
			->willReturnCallback(function ($path) use ($filePath, $fileDirectory) {
				return $path === $fileDirectory;
			});
		$this->rootFolder->method('getUserFolder')
			->willReturn($userFolder);

		$this->templateManager->createFromTemplate($filePath);
	}
}
