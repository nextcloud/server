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
namespace OCA\DAV\Tests\unit\Comments;

use OC\EventDispatcher\EventDispatcher;
use OCA\DAV\Comments\EntityTypeCollection as EntityTypeCollectionImplementation;
use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class RootCollectionTest extends \Test\TestCase {

	/** @var \OCP\Comments\ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $commentsManager;
	/** @var \OCP\IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var \OCA\DAV\Comments\RootCollection */
	protected $collection;
	/** @var \OCP\IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;
	/** @var IEventDispatcher */
	protected $dispatcher;
	/** @var \OCP\IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();

		$this->commentsManager = $this->getMockBuilder(ICommentsManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$this->dispatcher = new EventDispatcher(
			new \Symfony\Component\EventDispatcher\EventDispatcher(),
			\OC::$server,
			$this->logger
		);

		$this->collection = new \OCA\DAV\Comments\RootCollection(
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->dispatcher,
			$this->logger
		);
	}

	protected function prepareForInitCollections() {
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($this->user);

		$this->dispatcher->addListener(CommentsEntityEvent::class, function (CommentsEntityEvent $event): void {
			$event->addEntityCollection('files', function () {
				return true;
			});
		});
	}


	public function testCreateFile(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->createFile('foo');
	}


	public function testCreateDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->createDirectory('foo');
	}

	public function testGetChild(): void {
		$this->prepareForInitCollections();
		$etc = $this->collection->getChild('files');
		$this->assertTrue($etc instanceof EntityTypeCollectionImplementation);
	}


	public function testGetChildInvalid(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->prepareForInitCollections();
		$this->collection->getChild('robots');
	}


	public function testGetChildNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->getChild('files');
	}

	public function testGetChildren(): void {
		$this->prepareForInitCollections();
		$children = $this->collection->getChildren();
		$this->assertFalse(empty($children));
		foreach ($children as $child) {
			$this->assertTrue($child instanceof EntityTypeCollectionImplementation);
		}
	}


	public function testGetChildrenNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->getChildren();
	}

	public function testChildExistsYes(): void {
		$this->prepareForInitCollections();
		$this->assertTrue($this->collection->childExists('files'));
	}

	public function testChildExistsNo(): void {
		$this->prepareForInitCollections();
		$this->assertFalse($this->collection->childExists('robots'));
	}


	public function testChildExistsNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->childExists('files');
	}


	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->delete();
	}

	public function testGetName(): void {
		$this->assertSame('comments', $this->collection->getName());
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->setName('foobar');
	}

	public function testGetLastModified(): void {
		$this->assertSame(null, $this->collection->getLastModified());
	}
}
