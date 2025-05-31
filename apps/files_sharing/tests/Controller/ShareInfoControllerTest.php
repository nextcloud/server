<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\ShareInfoController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ShareInfoControllerTest extends TestCase {

	protected ShareInfoController $controller;
	protected ShareManager&MockObject $shareManager;


	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(ShareManager::class);

		$this->controller = new ShareInfoController(
			'files_sharing',
			$this->createMock(IRequest::class),
			$this->shareManager
		);
	}

	public function testNoShare(): void {
		$this->shareManager->method('getShareByToken')
			->with('token')
			->willThrowException(new ShareNotFound());

		$expected = new JSONResponse([], Http::STATUS_NOT_FOUND);
		$expected->throttle(['token' => 'token']);
		$this->assertEquals($expected, $this->controller->info('token'));
	}

	public function testWrongPassword(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getPassword')
			->willReturn('sharePass');

		$this->shareManager->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->with($share, 'pass')
			->willReturn(false);

		$expected = new JSONResponse([], Http::STATUS_FORBIDDEN);
		$expected->throttle(['token' => 'token']);
		$this->assertEquals($expected, $this->controller->info('token', 'pass'));
	}

	public function testNoReadPermissions(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getPassword')
			->willReturn('sharePass');
		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_CREATE);

		$this->shareManager->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->with($share, 'pass')
			->willReturn(true);

		$expected = new JSONResponse([], Http::STATUS_FORBIDDEN);
		$expected->throttle(['token' => 'token']);
		$this->assertEquals($expected, $this->controller->info('token', 'pass'));
	}

	private function prepareFile() {
		$file = $this->createMock(File::class);

		$file->method('getId')->willReturn(42);

		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(41);
		$file->method('getParent')->willReturn($parent);

		$file->method('getMTime')->willReturn(1337);
		$file->method('getName')->willReturn('file');
		$file->method('getPermissions')->willReturn(Constants::PERMISSION_READ);
		$file->method('getMimeType')->willReturn('mime/type');
		$file->method('getSize')->willReturn(1);
		$file->method('getType')->willReturn('file');
		$file->method('getEtag')->willReturn('etag');

		return $file;
	}

	public function testInfoFile(): void {
		$file = $this->prepareFile();

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')
			->willReturn('sharePass');
		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);
		$share->method('getNode')
			->willReturn($file);

		$this->shareManager->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->with($share, 'pass')
			->willReturn(true);

		$expected = new JSONResponse([
			'id' => 42,
			'parentId' => 41,
			'mtime' => 1337	,
			'name' => 'file',
			'permissions' => 1,
			'mimetype' => 'mime/type',
			'size' => 1,
			'type' => 'file',
			'etag' => 'etag',
		]);
		$this->assertEquals($expected, $this->controller->info('token', 'pass'));
	}

	public function testInfoFileRO(): void {
		$file = $this->prepareFile();

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')
			->willReturn('sharePass');
		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ);
		$share->method('getNode')
			->willReturn($file);

		$this->shareManager->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->with($share, 'pass')
			->willReturn(true);

		$expected = new JSONResponse([
			'id' => 42,
			'parentId' => 41,
			'mtime' => 1337	,
			'name' => 'file',
			'permissions' => 1,
			'mimetype' => 'mime/type',
			'size' => 1,
			'type' => 'file',
			'etag' => 'etag',
		]);
		$this->assertEquals($expected, $this->controller->info('token', 'pass'));
	}

	private function prepareFolder() {
		$root = $this->createMock(Folder::class);

		$root->method('getId')->willReturn(42);

		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(41);
		$root->method('getParent')->willReturn($parent);

		$root->method('getMTime')->willReturn(1337);
		$root->method('getName')->willReturn('root');
		$root->method('getPermissions')->willReturn(Constants::PERMISSION_READ);
		$root->method('getMimeType')->willReturn('mime/type');
		$root->method('getSize')->willReturn(1);
		$root->method('getType')->willReturn('folder');
		$root->method('getEtag')->willReturn('etag');


		//Subfolder
		$sub = $this->createMock(Folder::class);

		$sub->method('getId')->willReturn(43);
		$sub->method('getParent')->willReturn($root);
		$sub->method('getMTime')->willReturn(1338);
		$sub->method('getName')->willReturn('sub');
		$sub->method('getPermissions')->willReturn(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);
		$sub->method('getMimeType')->willReturn('mime/type');
		$sub->method('getSize')->willReturn(2);
		$sub->method('getType')->willReturn('folder');
		$sub->method('getEtag')->willReturn('etag2');

		$root->method('getDirectoryListing')->willReturn([$sub]);

		//Subfile
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(88);
		$file->method('getParent')->willReturn($sub);
		$file->method('getMTime')->willReturn(1339);
		$file->method('getName')->willReturn('file');
		$file->method('getPermissions')->willReturn(Constants::PERMISSION_READ | Constants::PERMISSION_DELETE);
		$file->method('getMimeType')->willReturn('mime/type');
		$file->method('getSize')->willReturn(3);
		$file->method('getType')->willReturn('file');
		$file->method('getEtag')->willReturn('etag3');

		$sub->method('getDirectoryListing')->willReturn([$file]);

		return $root;
	}

	public function testInfoFolder(): void {
		$file = $this->prepareFolder();

		$share = $this->createMock(IShare::class);
		$share->method('getPassword')
			->willReturn('sharePass');
		$share->method('getPermissions')
			->willReturn(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);
		$share->method('getNode')
			->willReturn($file);

		$this->shareManager->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->shareManager->method('checkPassword')
			->with($share, 'pass')
			->willReturn(true);

		$expected = new JSONResponse([
			'id' => 42,
			'parentId' => 41,
			'mtime' => 1337,
			'name' => 'root',
			'permissions' => 1,
			'mimetype' => 'mime/type',
			'size' => 1,
			'type' => 'folder',
			'etag' => 'etag',
			'children' => [
				[
					'id' => 43,
					'parentId' => 42,
					'mtime' => 1338,
					'name' => 'sub',
					'permissions' => 3,
					'mimetype' => 'mime/type',
					'size' => 2,
					'type' => 'folder',
					'etag' => 'etag2',
					'children' => [
						[
							'id' => 88,
							'parentId' => 43,
							'mtime' => 1339,
							'name' => 'file',
							'permissions' => 1,
							'mimetype' => 'mime/type',
							'size' => 3,
							'type' => 'file',
							'etag' => 'etag3',
						]
					],
				]
			],
		]);
		$this->assertEquals($expected, $this->controller->info('token', 'pass'));
	}
}
