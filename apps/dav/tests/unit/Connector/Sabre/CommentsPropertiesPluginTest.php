<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\CommentPropertiesPlugin as CommentPropertiesPluginImplementation;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCP\Comments\ICommentsManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

class CommentsPropertiesPluginTest extends \Test\TestCase {
	protected CommentPropertiesPluginImplementation $plugin;
	protected ICommentsManager&MockObject $commentsManager;
	protected IUserSession&MockObject $userSession;
	protected Server&MockObject $server;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->server = $this->createMock(Server::class);

		$this->plugin = new CommentPropertiesPluginImplementation($this->commentsManager, $this->userSession);
		$this->plugin->initialize($this->server);
	}

	public static function nodeProvider(): array {
		return [
			[File::class, true],
			[Directory::class, true],
			[\Sabre\DAV\INode::class, false]
		];
	}

	/**
	 * @dataProvider nodeProvider
	 */
	public function testHandleGetProperties(string $class, bool $expectedSuccessful): void {
		$propFind = $this->createMock(PropFind::class);

		if ($expectedSuccessful) {
			$propFind->expects($this->exactly(3))
				->method('handle');
		} else {
			$propFind->expects($this->never())
				->method('handle');
		}

		$node = $this->createMock($class);
		$this->plugin->handleGetProperties($propFind, $node);
	}

	public static function baseUriProvider(): array {
		return [
			['owncloud/remote.php/webdav/', '4567', 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/remote.php/files/', '4567', 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/wicked.php/files/', '4567', null]
		];
	}

	/**
	 * @dataProvider baseUriProvider
	 */
	public function testGetCommentsLink(string $baseUri, string $fid, ?string $expectedHref): void {
		$node = $this->createMock(File::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn($fid);

		$this->server->expects($this->once())
			->method('getBaseUri')
			->willReturn($baseUri);

		$href = $this->plugin->getCommentsLink($node);
		$this->assertSame($expectedHref, $href);
	}

	public static function userProvider(): array {
		return [
			[IUser::class],
			[null]
		];
	}

	/**
	 * @dataProvider userProvider
	 */
	public function testGetUnreadCount(?string $user): void {
		$node = $this->createMock(File::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn('4567');

		if ($user !== null) {
			$user = $this->createMock($user);
		}
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->commentsManager->expects($this->any())
			->method('getNumberOfCommentsForObject')
			->willReturn(42);

		$unread = $this->plugin->getUnreadCount($node);
		if (is_null($user)) {
			$this->assertNull($unread);
		} else {
			$this->assertSame($unread, 42);
		}
	}
}
