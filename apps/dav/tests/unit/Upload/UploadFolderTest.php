<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\FutureFile;
use OCA\DAV\Upload\UploadFolder;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use OCP\Server;
use Test\TestCase;
use Test\Traits\UserTrait;

/**
 * The desktop client deletes an upload session when it believes the assembly
 * failed - on an ambiguous 502, for instance. If the assembly is in fact still
 * running, that pulls the chunks out from under it while it reads them.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class UploadFolderTest extends TestCase {
	use UserTrait;

	private string $user;
	private View $uploadsView;
	private Directory $sessionNode;

	protected function setUp(): void {
		parent::setUp();

		$this->user = self::getUniqueID('upload_folder_');
		$this->createUser($this->user, 'pass');
		self::loginAsUser($this->user);
		Server::get(IRootFolder::class)->getUserFolder($this->user);

		$userView = new View('/' . $this->user);
		if (!$userView->file_exists('uploads')) {
			$userView->mkdir('uploads');
		}

		$this->uploadsView = new View('/' . $this->user . '/uploads');
		$this->uploadsView->mkdir('session-1');
		$this->uploadsView->file_put_contents('session-1/00001', 'chunk data');

		$this->sessionNode = new Directory(
			$this->uploadsView,
			$this->uploadsView->getFileInfo('session-1')
		);
	}

	protected function tearDown(): void {
		Server::get(IUserManager::class)->get($this->user)?->delete();
		parent::tearDown();
	}

	private function buildUploadFolder(): UploadFolder {
		return new UploadFolder(
			$this->sessionNode,
			Server::get(CleanupService::class),
			$this->uploadsView->getFileInfo('session-1')->getStorage(),
			$this->user,
		);
	}

	public function testDeleteIsRefusedWhileTheChunksAreBeingAssembled(): void {
		// a MOVE running in another request holds this while it streams the chunks
		$assembling = new FutureFile($this->sessionNode, '.file');
		$assembling->lockAssembly();

		try {
			$this->buildUploadFolder()->delete();
			$this->fail('Expected the delete to be refused while assembling');
		} catch (FileLocked $e) {
			// expected
		}

		$this->assertTrue(
			$this->uploadsView->file_exists('session-1/00001'),
			'The chunks the assembly is reading must still be there'
		);

		$assembling->unlockAssembly();
	}

	public function testDeleteWorksOnceTheAssemblyReleasedTheChunks(): void {
		$assembling = new FutureFile($this->sessionNode, '.file');
		$assembling->lockAssembly();
		$assembling->unlockAssembly();

		$this->buildUploadFolder()->delete();

		$this->assertFalse($this->uploadsView->file_exists('session-1'));
	}

	public function testDeleteWorksWhenNoAssemblyIsRunning(): void {
		$this->buildUploadFolder()->delete();

		$this->assertFalse($this->uploadsView->file_exists('session-1'));
	}
}
