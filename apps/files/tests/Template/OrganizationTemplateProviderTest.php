<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Tests\Template;

use OCA\Files\Template\OrganizationTemplateProvider;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OrganizationTemplateProviderTest extends TestCase {
	private OrganizationTemplateProvider $provider;
	private IAppConfig&MockObject $config;
	private Folder&MockObject $folder;
	private Folder&MockObject $userFolder;
	private IURLGenerator&MockObject $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$root = $this->createMock(IRootFolder::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->userFolder = $this->createMock(Folder::class);
		$root->method('getUserFolder')->with('admin')->willReturn($this->userFolder);
		$this->folder = $this->createMock(Folder::class);
		$this->folder->method('isReadable')->willReturn(true);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->provider = new OrganizationTemplateProvider($root, $this->config, $this->urlGenerator);
	}

	private function configure(): void {
		$this->config->method('getValueArray')->willReturn(['owner' => 'admin', 'folder' => 42]);
		$this->userFolder->method('getById')->with(42)->willReturn([$this->folder]);
	}

	public function testStoresOwnerAndStableFolderIdTogether(): void {
		$this->config->expects(self::once())->method('setValueArray')->with('files', 'organization_template_folder', ['owner' => 'admin', 'folder' => 42]);
		$this->provider->setSelection('admin', 42);
	}

	public function testUnconfiguredFolderHasNoTemplates(): void {
		$this->config->method('getValueArray')->willReturn([]);
		$this->userFolder->expects(self::never())->method('getById');
		self::assertSame([], $this->provider->getCustomTemplates('text/plain'));
	}

	public function testDeletedFolderHasNoTemplates(): void {
		$this->config->method('getValueArray')->willReturn(['owner' => 'admin', 'folder' => 42]);
		$this->userFolder->method('getById')->willReturn([]);
		self::assertSame([], $this->provider->getCustomTemplates('text/plain'));
	}

	public function testListsOnlyReadableFilesOfRequestedMime(): void {
		$this->configure();
		$file = $this->createMock(File::class);
		$file->method('isReadable')->willReturn(true);
		$file->method('getId')->willReturn(12);
		$unreadable = $this->createMock(File::class);
		$this->folder->method('searchByMime')->with('text/plain')->willReturn([$file, $unreadable, $this->folder]);
		$this->urlGenerator->expects(self::once())->method('linkToOCSRouteAbsolute')->with('files.OrganizationTemplate.preview', ['id' => '12', 'etag' => ''])->willReturn('/ocs/v2.php/template-preview');
		$templates = $this->provider->getCustomTemplates('text/plain');
		self::assertCount(1, $templates);
		self::assertSame('12', $templates[0]->jsonSerialize()['templateId']);
		self::assertSame('/ocs/v2.php/template-preview', $templates[0]->jsonSerialize()['previewUrl']);
		self::assertSame(OrganizationTemplateProvider::class, $templates[0]->jsonSerialize()['templateType']);
	}

	public function testResolvesOnlyWithinPublishedFolder(): void {
		$this->configure();
		$file = $this->createMock(File::class);
		$file->method('isReadable')->willReturn(true);
		$this->folder->method('getById')->with(12)->willReturn([$file]);
		$this->folder->method('getRelativePath')->willReturn('/Letter.txt');
		self::assertSame($file, $this->provider->getCustomTemplate('12'));
	}

	public function testCannotResolveUnpublishedFile(): void {
		$this->configure();
		$this->folder->method('getById')->with(999)->willReturn([]);
		$this->expectException(NotFoundException::class);
		$this->provider->getCustomTemplate('999');
	}

	public function testRejectsPathAsIdentifier(): void {
		$this->userFolder->expects(self::never())->method('getById');
		$this->expectException(NotFoundException::class);
		$this->provider->getCustomTemplate('../private.txt');
	}
}
