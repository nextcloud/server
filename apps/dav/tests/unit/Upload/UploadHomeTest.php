<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\UploadFolder;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound as SabreNotFound;
use Test\TestCase;
use Test\Traits\UserTrait;

class UploadHomeTest extends TestCase {
	use UserTrait;

	private array $principalInfo;
	private IRootFolder $rootFolder;

	protected function setUp(): void {
		parent::setUp();

		// Create and "log in" a test user
		$user = $this->createUser('testuser', 'testpass');
		// We use a mocked IUserSession in the tests, so no explicit login call is needed.

		$this->principalInfo = [
			'uri' => 'principals/users/testuser'
		];

		// Use the real root folder to avoid mocking low-level filesystem behavior
		$this->rootFolder = \OC::$server->get(IRootFolder::class);
	}

	private function makeUserSessionReturningTestUser(): IUserSession {
		$userSession = $this->createMock(IUserSession::class);
		$user = $this->getUser('testuser');
		$userSession->method('getUser')->willReturn($user);
		return $userSession;
	}

	private function ensureUploadsFolderExists(string $uid = 'testuser'): Folder {
		$path = '/' . $uid . '/uploads';
		try {
			$node = $this->rootFolder->get($path);
			if (!$node instanceof Folder) {
				$this->fail('Uploads path exists but is not a folder');
			}
			return $node;
		} catch (NotFoundException) {
			return $this->rootFolder->newFolder($path);
		}
	}

	private function removeUploadsFolderIfExists(string $uid = 'testuser'): void {
		$path = '/' . $uid . '/uploads';
		try {
			$node = $this->rootFolder->get($path);
			$node->delete();
		} catch (NotFoundException) {
			// already gone
		}
	}

	private function makeUploadHomeForUser(CleanupService $cleanup = null, ?IManager $shareManager = null): UploadHome {
		$cleanup ??= $this->createMock(CleanupService::class);
		$shareManager ??= $this->createMock(IManager::class);
		$userSession = $this->makeUserSessionReturningTestUser();

		return new UploadHome(
			$this->principalInfo,
			$cleanup,
			$this->rootFolder,
			$userSession,
			$shareManager
		);
	}

	private function makeUploadHomeForShare(): UploadHome {
		$cleanup = $this->createMock(CleanupService::class);
		$shareManager = $this->createMock(IManager::class);

		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getShareOwner')->willReturn('shareowner');
		$shareManager->method('getShareByToken')->with('sometoken')->willReturn($shareMock);

		$userSession = $this->makeUserSessionReturningTestUser();

		$principalInfo = [
			'uri' => 'principals/shares/sometoken'
		];

		return new UploadHome(
			$principalInfo,
			$cleanup,
			$this->rootFolder,
			$userSession,
			$shareManager
		);
	}

	public function testUserConstructorSetsUid(): void {
		$uploadHome = $this->makeUploadHomeForUser();
		$reflector = new \ReflectionClass($uploadHome);
		$uidProp = $reflector->getProperty('uid');
		$uidProp->setAccessible(true);
		$this->assertEquals('testuser', $uidProp->getValue($uploadHome));
	}

	public function testShareConstructorSetsUid(): void {
		$uploadHome = $this->makeUploadHomeForShare();
		$ref = new \ReflectionClass($uploadHome);
		$uidProp = $ref->getProperty('uid');
		$uidProp->setAccessible(true);
		$this->assertEquals('shareowner', $uidProp->getValue($uploadHome));
	}

	public function testConstructorThrowsIfUserMissing(): void {
		$cleanup = $this->createMock(CleanupService::class);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);
		$shareManager = $this->createMock(IManager::class);

		$this->expectException(Forbidden::class);
		new UploadHome(
			$this->principalInfo,
			$cleanup,
			$this->rootFolder,
			$userSession,
			$shareManager
		);
	}

	public function testCreateFileThrowsForbidden(): void {
		$uploadHome = $this->makeUploadHomeForUser();
		$this->expectException(Forbidden::class);
		$uploadHome->createFile('denied.txt');
	}

	public function testSetNameThrowsForbidden(): void {
		$uploadHome = $this->makeUploadHomeForUser();
		$this->expectException(Forbidden::class);
		$uploadHome->setName('denied');
	}

	public function testCreateDirectoryAddsCleanupJob(): void {
		$this->removeUploadsFolderIfExists(); // clean slate
		$this->ensureUploadsFolderExists();

		$cleanup = $this->createMock(CleanupService::class);
		$cleanup->expects($this->once())->method('addJob')->with('testuser', 'foo');

		$uploadHome = $this->makeUploadHomeForUser($cleanup);

		// Should create the sub-directory and schedule cleanup
		$uploadHome->createDirectory('foo');

		// Verify the directory was created on disk
		$path = '/testuser/uploads/foo';
		$node = $this->rootFolder->get($path);
		$this->assertInstanceOf(Folder::class, $node);
	}

	public function testGetChildReturnsUploadFolder(): void {
		// Prepare: ensure child exists
		$uploads = $this->ensureUploadsFolderExists();
		try { $uploads->newFolder('childname'); } catch (\Throwable) { /* ignore if exists */ }

		$uploadHome = $this->makeUploadHomeForUser();

		$result = $uploadHome->getChild('childname');
		$this->assertInstanceOf(UploadFolder::class, $result);
	}

	public function testGetChildThrowsExceptionIfNotFound(): void {
		$uploads = $this->ensureUploadsFolderExists();
		// Ensure the child does not exist
		try {
			$child = $this->rootFolder->get('/testuser/uploads/missing');
			$child->delete();
		} catch (NotFoundException) {
			// fine
		}

		$uploadHome = $this->makeUploadHomeForUser();

		$this->expectException(SabreNotFound::class);
		$uploadHome->getChild('missing');
	}

	public function testGetChildrenReturnsUploadFolders(): void {
		$uploads = $this->ensureUploadsFolderExists();
		try { $uploads->newFolder('a'); } catch (\Throwable) {}
		try { $uploads->newFolder('b'); } catch (\Throwable) {}

		$uploadHome = $this->makeUploadHomeForUser();

		$children = $uploadHome->getChildren();
		$this->assertIsArray($children);
		$this->assertNotEmpty($children);
		$this->assertContainsOnlyInstancesOf(UploadFolder::class, $children);
	}

	public function testChildExistsReturnsTrueIfChildExists(): void {
		$uploads = $this->ensureUploadsFolderExists();
		try { $uploads->newFolder('child'); } catch (\Throwable) {}

		$uploadHome = $this->makeUploadHomeForUser();

		$this->assertTrue($uploadHome->childExists('child'));
	}

	public function testChildExistsReturnsFalseIfNoChild(): void {
		// This test documents the current behavior: childExists() will throw SabreNotFound via getChild()
		// The current implementation is broken. Instead of returning false, it throws if the child is missing, 
		// due to not catching the NotFound exception.  This matches the current UploadHome implementation, but 
		// should be fixed.
		$uploads = $this->ensureUploadsFolderExists();
		// Ensure it does not exist
		try {
			$child = $this->rootFolder->get('/testuser/uploads/nochil');
			$child->delete();
		} catch (NotFoundException) {
			// ok
		}

		$uploadHome = $this->makeUploadHomeForUser();

		$this->expectException(SabreNotFound::class);
		$uploadHome->childExists('nochil');
	}

	public function testDeleteProxiesToImplDelete(): void {
		// Ensure uploads exists, then delete via UploadHome
		$this->ensureUploadsFolderExists();

		$uploadHome = $this->makeUploadHomeForUser();
		$uploadHome->delete();

		// Verify folder was removed
		$this->expectException(NotFoundException::class);
		$this->rootFolder->get('/testuser/uploads');
	}

	public function testGetNameReturnsPrincipalName(): void {
		$uploadHome = $this->makeUploadHomeForUser();
		$this->assertEquals('testuser', $uploadHome->getName());
	}

	public function testGetLastModifiedProxiesToImpl(): void {
		// Touch uploads folder to ensure it exists
		$this->ensureUploadsFolderExists();

		$uploadHome = $this->makeUploadHomeForUser();

		$mtime = $uploadHome->getLastModified();
		$this->assertIsInt($mtime);
		$this->assertGreaterThan(0, $mtime);
	}
}
