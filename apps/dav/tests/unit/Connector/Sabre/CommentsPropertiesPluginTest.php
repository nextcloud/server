<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\CommentPropertiesPlugin as CommentPropertiesPluginImplementation;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use OCP\Comments\ICommentsManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Test\TestCase;

class CommentsPropertiesPluginTest extends TestCase {

	/** @var  CommentPropertiesPluginImplementation */
	protected $plugin;
	protected $commentsManager;
	protected $userSession;
	protected $server;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->server = $this->createMock(Server::class);

		$this->plugin = new CommentPropertiesPluginImplementation($this->commentsManager, $this->userSession);
		$this->plugin->initialize($this->server);
	}

	public function nodeProvider(): array {
		$mocks = [];
		foreach ([File::class, Directory::class, INode::class] as $class) {
			$mocks[] = $this->createMock($class);
		}

		return [
			[$mocks[0], true],
			[$mocks[1], true],
			[$mocks[2], false]
		];
	}

	/**
	 * @dataProvider nodeProvider
	 */
	public function testHandleGetProperties($node, bool $expectedSuccessful) {
		$propFind = $this->createMock(PropFind::class);

		if ($expectedSuccessful) {
			$propFind->expects($this->exactly(3))
				->method('handle');
		} else {
			$propFind->expects($this->never())
				->method('handle');
		}

		$this->plugin->handleGetProperties($propFind, $node);
	}

	public function baseUriProvider(): array {
		return [
			['owncloud/remote.php/webdav/', 4567, 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/remote.php/files/', 4567, 'owncloud/remote.php/dav/comments/files/4567'],
			['owncloud/wicked.php/files/', 4567, null]
		];
	}

	/**
	 * @dataProvider baseUriProvider
	 */
	public function testGetCommentsLink(string $baseUri, int $fid, ?string $expectedHref) {
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

	public function userProvider(): array {
		return [
			[
				$this->createMock(IUser::class)
			],
			[null]
		];
	}

	/**
	 * @dataProvider userProvider
	 * @param IUser|MockObject|null $user
	 */
	public function testGetUnreadCount($user) {
		$node = $this->createMock(File::class);
		$node->expects($this->any())
			->method('getId')
			->willReturn(4567);

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
			$this->assertSame(42, $unread);
		}
	}
}
