<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Controller;

use OCA\Files\Controller\TemplateController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Template\ITemplateManager;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TemplateControllerTest extends TestCase {
	private ITemplateManager&MockObject $manager;
	private IRootFolder&MockObject $root;
	private Folder&MockObject $userFolder;
	private TemplateController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->createMock(ITemplateManager::class);
		$this->root = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->root->method('getUserFolder')->with('alice')->willReturn($this->userFolder);
		$this->controller = new TemplateController('files', $this->createMock(IRequest::class), $this->manager, $this->root, 'alice');
		$this->manager->expects(self::never())->method('initializeTemplateDirectory');
		$this->userFolder->expects(self::never())->method('getOrCreateFolder');
	}

	public function testGetUnconfiguredPath(): void {
		$this->manager->method('getTemplatePath')->willReturn('');
		$this->userFolder->expects(self::never())->method('get');
		self::assertSame(['template_path' => '', 'available' => false], $this->controller->getPath()->getData());
	}

	public function testGetReadablePath(): void {
		$this->manager->method('getTemplatePath')->willReturn('/Templates');
		$folder = $this->createMock(Folder::class);
		$folder->method('isReadable')->willReturn(true);
		$this->userFolder->method('get')->with('/Templates')->willReturn($folder);
		self::assertSame(['template_path' => '/Templates', 'available' => true], $this->controller->getPath()->getData());
	}

	public static function unavailablePaths(): array {
		return [
			'file' => ['file'],
			'unreadable folder' => ['unreadable'],
			'missing folder' => [NotFoundException::class],
			'permission denied' => [NotPermittedException::class],
			'invalid path' => [InvalidPathException::class],
		];
	}

	#[DataProvider('unavailablePaths')]
	public function testGetUnavailablePath(string $reason): void {
		$this->manager->method('getTemplatePath')->willReturn('/Templates');
		$this->mockUnavailablePath($reason);
		$this->manager->expects(self::never())->method('setTemplatePath');
		self::assertSame(['template_path' => '/Templates', 'available' => false], $this->controller->getPath()->getData());
	}

	#[DataProvider('unavailablePaths')]
	public function testRejectUnavailableSelection(string $reason): void {
		$this->mockUnavailablePath($reason);
		$this->manager->expects(self::never())->method('setTemplatePath');
		$this->expectException(in_array($reason, ['unreadable', NotPermittedException::class], true) ? OCSForbiddenException::class : OCSBadRequestException::class);
		$this->controller->setPath('/Templates');
	}

	private function mockUnavailablePath(string $reason): void {
		if ($reason === 'file') {
			$this->userFolder->method('get')->willReturn($this->createMock(File::class));
		} elseif ($reason === 'unreadable') {
			$folder = $this->createMock(Folder::class);
			$folder->method('isReadable')->willReturn(false);
			$this->userFolder->method('get')->willReturn($folder);
		} else {
			$this->userFolder->method('get')->willThrowException(new $reason());
		}
	}

	public function testSelectExistingReadableFolder(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('isReadable')->willReturn(true);
		$folder->method('getPath')->willReturn('/alice/files/Team/Templates');
		$this->userFolder->method('get')->with('Team/Templates/')->willReturn($folder);
		$this->userFolder->method('getRelativePath')->with('/alice/files/Team/Templates')->willReturn('/Team/Templates');
		$this->manager->expects(self::once())->method('setTemplatePath')->with('/Team/Templates');
		self::assertSame(['template_path' => '/Team/Templates', 'available' => true], $this->controller->setPath('Team/Templates/')->getData());
	}

	public function testRejectFolderOutsideUserRoot(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('isReadable')->willReturn(true);
		$folder->method('getPath')->willReturn('/bob/files/Templates');
		$this->userFolder->method('get')->willReturn($folder);
		$this->userFolder->method('getRelativePath')->willReturn(null);
		$this->manager->expects(self::never())->method('setTemplatePath');
		$this->expectException(OCSBadRequestException::class);
		$this->controller->setPath('../bob/files/Templates');
	}

	public function testClearSelection(): void {
		$this->userFolder->expects(self::never())->method('get');
		$this->manager->expects(self::once())->method('setTemplatePath')->with('');
		self::assertSame(['template_path' => '', 'available' => false], $this->controller->setPath('')->getData());
	}
}
