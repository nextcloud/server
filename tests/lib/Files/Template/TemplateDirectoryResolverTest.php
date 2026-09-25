<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Template;

use OC\Files\Template\TemplateDirectoryResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use Test\TestCase;

class TemplateDirectoryResolverTest extends TestCase {
	private function folder(string $path, string $mount = '/alice/files/', bool $readable = true): Folder {
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($path);
		$folder->method('isReadable')->willReturn($readable);
		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountPoint')->willReturn($mount);
		$folder->method('getMountPoint')->willReturn($mountPoint);
		return $folder;
	}

	public function testFindsNearestAndAncestorTemplates(): void {
		$root = $this->folder('/alice/files');
		$child = $this->folder('/alice/files/Documents');
		$local = $this->folder('/alice/files/Documents/.Templates');
		$global = $this->folder('/alice/files/.Templates');
		$root->method('get')->willReturnMap([['Documents', $child], ['.Templates', $global]]);
		$root->method('getRelativePath')->willReturnCallback(fn ($path) => substr($path, strlen('/alice/files')));
		$child->method('get')->with('.Templates')->willReturn($local);
		$child->method('getParent')->willReturn($root);
		$root->expects(self::never())->method('getParent');
		self::assertSame([$local, $global], (new TemplateDirectoryResolver())->resolve($root, 'Documents'));
	}

	public function testStopsAtGroupFolderMountBoundary(): void {
		$root = $this->folder('/alice/files');
		$group = $this->folder('/alice/files/Team', '/alice/files/Team/');
		$templates = $this->folder('/alice/files/Team/.Templates', '/alice/files/Team/');
		$root->expects(self::once())->method('get')->with('Team')->willReturn($group);
		$root->method('getRelativePath')->willReturnCallback(fn ($path) => substr($path, strlen('/alice/files')));
		$group->method('get')->with('.Templates')->willReturn($templates);
		$group->method('getParent')->willReturn($root);
		self::assertSame([$templates], (new TemplateDirectoryResolver())->resolve($root, 'Team'));
	}

	public function testSkipsMissingAndUnreadableTemplateFolders(): void {
		$root = $this->folder('/alice/files');
		$child = $this->folder('/alice/files/Documents');
		$unreadable = $this->folder('/alice/files/Documents/.Templates', readable: false);
		$root->method('get')->willReturnCallback(function ($path) use ($child) {
			if ($path === 'Documents') {
				return $child;
			}
			throw new NotFoundException();
		});
		$root->method('getRelativePath')->willReturn('');
		$child->method('get')->willReturn($unreadable);
		$child->method('getParent')->willReturn($root);
		self::assertSame([], (new TemplateDirectoryResolver())->resolve($root, 'Documents'));
	}

	public function testIgnoresFileAsDestination(): void {
		$root = $this->folder('/alice/files');
		$root->method('get')->willReturn($this->createMock(File::class));
		self::assertSame([], (new TemplateDirectoryResolver())->resolve($root, 'file.txt'));
	}

	public function testIgnoresMissingDestination(): void {
		$root = $this->folder('/alice/files');
		$root->method('get')->willThrowException(new NotFoundException());
		self::assertSame([], (new TemplateDirectoryResolver())->resolve($root, 'missing'));
	}
}
