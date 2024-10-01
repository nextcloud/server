<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\CommentPropertiesPlugin as CommentPropertiesPluginImplementation;
use OCA\DAV\Connector\Sabre\File;
use OCP\Comments\ICommentsManager;
use OCP\IUser;
use OCP\IUserSession;
use Sabre\DAV\PropFind;

class CommentsPropertiesPluginTest extends \Test\TestCase {

	/** @var CommentPropertiesPluginImplementation */
	protected $plugin;
	protected $commentsManager;
	protected $userSession;
	protected $server;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->getMockBuilder(ICommentsManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->disableOriginalConstructor()
			->getMock();

		$this->plugin = new CommentPropertiesPluginImplementation($this->commentsManager, $this->userSession);
		$this->plugin->initialize($this->server);
	}

	public function nodeProvider() {
		$mocks = [];
		foreach (['\OCA\DAV\Connector\Sabre\File', '\OCA\DAV\Connector\Sabre\Directory', '\Sabre\DAV\INode'] as $class) {
			$mocks[] = $this->getMockBuilder($class)
				->disableOriginalConstructor()
				->getMock();
		}

		return [
			[$mocks[0], true],
			[$mocks[1], true],
			[$mocks[2], false]
		];
	}

	/**
	 * @dataProvider nodeProvider
	 * @param $node
	 * @param $expectedSuccessful
	 */
	public function testHandleGetProperties($node, $expectedSuccessful): void {
		$propFind = $this->getMockBuilder(PropFind::class)
			->disableOriginalConstructor()
			->getMock();

		if ($expectedSuccessful) {
			$propFind->expects($this->exactly(3))
				->method('handle');
		} else {
			$propFind->expects($this->never())
				->method('handle');
		}

		$this->plugin->handleGetProperties($propFind, $node);
	}

	public function baseUriProvider() {
		return [
			['owncloud/remote.php/webdav/', '4567', 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/remote.php/files/', '4567', 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/wicked.php/files/', '4567', null]
		];
	}

	/**
	 * @dataProvider baseUriProvider
	 * @param $baseUri
	 * @param $fid
	 * @param $expectedHref
	 */
	public function testGetCommentsLink($baseUri, $fid, $expectedHref): void {
		$node = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->willReturn($fid);

		$this->server->expects($this->once())
			->method('getBaseUri')
			->willReturn($baseUri);

		$href = $this->plugin->getCommentsLink($node);
		$this->assertSame($expectedHref, $href);
	}

	public function userProvider() {
		return [
			[
				$this->getMockBuilder(IUser::class)
					->disableOriginalConstructor()
					->getMock()
			],
			[null]
		];
	}

	/**
	 * @dataProvider userProvider
	 * @param $user
	 */
	public function testGetUnreadCount($user): void {
		$node = $this->getMockBuilder(File::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->any())
			->method('getId')
			->willReturn('4567');

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
