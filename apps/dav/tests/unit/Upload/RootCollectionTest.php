<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\RootCollection;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;
use Test\TestCase;

class RootCollectionTest extends TestCase {
	private BackendInterface&MockObject $principalBackend;
	private CleanupService&MockObject $cleanupService;
	private IRootFolder&MockObject $rootFolder;
	private IUserSession&MockObject $userSession;
	private IShareManager&MockObject $shareManager;
	private RootCollection $collection;

	protected function setUp(): void {
		parent::setUp();

		$this->principalBackend = $this->createMock(BackendInterface::class);
		$this->cleanupService = $this->createMock(CleanupService::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->shareManager = $this->createMock(IShareManager::class);

		$this->collection = new RootCollection(
			$this->principalBackend,
			'principals/users',
			$this->cleanupService,
			$this->rootFolder,
			$this->userSession,
			$this->shareManager,
		);
	}

	private function mockUser(string $uid): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		return $user;
	}

	public function testGetChildForPrincipalReturnsUploadHomeForOwnPrincipal(): void {
		$this->userSession->method('getUser')->willReturn($this->mockUser('alice'));

		$node = $this->collection->getChildForPrincipal(['uri' => 'principals/users/alice']);

		$this->assertInstanceOf(UploadHome::class, $node);
	}

	public function testGetChildForPrincipalReturnsUploadHomeForShareToken(): void {
		$this->userSession->method('getUser')->willReturn($this->mockUser('alice'));

		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn('bob');
		$this->shareManager->method('getShareByToken')
			->with('sometoken')
			->willReturn($share);

		$node = $this->collection->getChildForPrincipal(['uri' => 'principals/shares/sometoken']);

		$this->assertInstanceOf(UploadHome::class, $node);
	}

	public function testGetChildForPrincipalThrowsWhenPrincipalDoesNotMatchUser(): void {
		$this->userSession->method('getUser')->willReturn($this->mockUser('alice'));

		$this->expectException(Forbidden::class);

		$this->collection->getChildForPrincipal(['uri' => 'principals/users/bob']);
	}

	public function testGetChildForPrincipalThrowsWhenNotLoggedIn(): void {
		$this->userSession->method('getUser')->willReturn(null);

		$this->expectException(Forbidden::class);

		$this->collection->getChildForPrincipal(['uri' => 'principals/users/alice']);
	}
}
