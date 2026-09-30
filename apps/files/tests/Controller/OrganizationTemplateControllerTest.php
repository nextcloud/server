<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Controller;

use OCA\Files\Controller\OrganizationTemplateController;
use OCA\Files\Template\OrganizationTemplateProvider;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OrganizationTemplateControllerTest extends TestCase {
	private OrganizationTemplateController $controller;
	private OrganizationTemplateProvider&MockObject $provider;
	private Folder&MockObject $userFolder;

	protected function setUp(): void {
		parent::setUp();
		$this->provider = $this->createMock(OrganizationTemplateProvider::class);
		$this->provider->method('getSelection')->willReturn(['owner' => 'admin', 'folder' => 42]);
		$root = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);
		$root->method('getUserFolder')->with('admin')->willReturn($this->userFolder);
		$this->controller = new OrganizationTemplateController($this->createMock(IRequest::class), $this->provider, $root, $this->createMock(IPreview::class), 'admin');
	}

	public function testOnlyAdminsMayReadOrChangeSelection(): void {
		foreach (['getPath', 'setPath'] as $name) {
			$method = new \ReflectionMethod(OrganizationTemplateController::class, $name);
			self::assertSame([], $method->getAttributes(NoAdminRequired::class));
			self::assertSame([], $method->getAttributes(PublicPage::class));
		}
		$preview = new \ReflectionMethod(OrganizationTemplateController::class, 'preview');
		self::assertCount(1, $preview->getAttributes(NoAdminRequired::class));
		self::assertSame([], $preview->getAttributes(PublicPage::class));
	}

	public function testReturnsUnavailableSelection(): void {
		$this->provider->method('getFolder')->willThrowException(new NotFoundException());
		self::assertSame(['template_path' => '', 'available' => false, 'owner' => 'admin'], $this->controller->getPath()->getData());
	}

	public function testPublishesReadableFolder(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getId')->willReturn(42);
		$folder->method('isReadable')->willReturn(true);
		$this->userFolder->method('get')->with('/Templates')->willReturn($folder);
		$this->userFolder->method('getRelativePath')->willReturn('/Templates');
		$this->provider->method('getFolder')->willReturn($folder);
		$this->provider->expects(self::once())->method('setSelection')->with('admin', 42);
		self::assertSame(['template_path' => '/Templates', 'available' => true, 'owner' => 'admin'], $this->controller->setPath('/Templates')->getData());
	}

	public function testRejectsUnreadableFolder(): void {
		$this->userFolder->method('get')->willReturn($this->createMock(Folder::class));
		$this->provider->expects(self::never())->method('setSelection');
		$this->expectException(OCSBadRequestException::class);
		$this->controller->setPath('/Private');
	}

	public function testRejectsMissingFolder(): void {
		$this->userFolder->method('get')->willThrowException(new NotFoundException());
		$this->provider->expects(self::never())->method('setSelection');
		$this->expectException(OCSBadRequestException::class);
		$this->controller->setPath('/Missing');
	}

	public function testClearsWithoutDeletingFiles(): void {
		$this->userFolder->expects(self::never())->method('get');
		$this->provider->expects(self::once())->method('setSelection')->with('', 0);
		$this->provider->method('getFolder')->willThrowException(new NotFoundException());
		$this->controller->setPath('');
	}

	public function testPreviewCannotReadUnpublishedFile(): void {
		$this->provider->method('getCustomTemplate')->with('99')->willThrowException(new NotFoundException());
		$this->expectException(OCSNotFoundException::class);
		$this->controller->preview('99');
	}
}
