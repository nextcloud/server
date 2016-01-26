<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\Unit\Comments;

use OCA\DAV\Comments\CommentNode;

class CommentsNode extends \Test\TestCase {

	protected $commentsManager;
	protected $comment;
	protected $node;
	protected $userManager;
	protected $logger;

	public function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMock('\OCP\Comments\ICommentsManager');
		$this->comment = $this->getMock('\OCP\Comments\IComment');
		$this->userManager = $this->getMock('\OCP\IUserManager');
		$this->logger = $this->getMock('\OCP\ILogger');

		$this->node = new CommentNode($this->commentsManager, $this->comment, $this->userManager, $this->logger);
	}

	public function testDelete() {
		$this->comment->expects($this->once())
			->method('getId')
			->will($this->returnValue('19'));

		$this->commentsManager->expects($this->once())
			->method('delete')
			->with('19');

		$this->node->delete();
	}

	public function testGetName() {
		$id = '19';
		$this->comment->expects($this->once())
			->method('getId')
			->will($this->returnValue($id));

		$this->assertSame($this->node->getName(), $id);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testSetName() {
		$this->node->setName('666');
	}

	public function testGetLastModified() {
		$this->assertSame($this->node->getLastModified(), null);
	}

	public function testUpdateComment() {
		$msg = 'Hello Earth';

		$this->comment->expects($this->once())
			->method('setMessage')
			->with($msg);

		$this->commentsManager->expects($this->once())
			->method('save')
			->with($this->comment);

		$this->assertTrue($this->node->updateComment($msg));
	}

	public function testUpdateCommentException() {
		$msg = null;

		$this->comment->expects($this->once())
			->method('setMessage')
			->with($msg)
			->will($this->throwException(new \Exception('buh!')));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->logger->expects($this->once())
			->method('logException');

		$this->assertFalse($this->node->updateComment($msg));
	}

	public function testPropPatch() {
		$propPatch = $this->getMockBuilder('Sabre\DAV\PropPatch')
			->disableOriginalConstructor()
			->getMock();

		$propPatch->expects($this->once())
			->method('handle')
			->with('{http://owncloud.org/ns}message');

		$propPatch->expects($this->once())
			->method('commit');

		$this->node->propPatch($propPatch);
	}

	public function testGetProperties() {
		$ns = '{http://owncloud.org/ns}';
		$expected = [
			$ns . 'id' => '123',
			$ns . 'parentId' => '12',
			$ns . 'topmostParentId' => '2',
			$ns . 'childrenCount' => 3,
			$ns . 'message' => 'such a nice file you have…',
			$ns . 'verb' => 'comment',
			$ns . 'actorType' => 'users',
			$ns . 'actorId' => 'alice',
			$ns . 'actorDisplayName' => 'Alice of Wonderland',
			$ns . 'creationDateTime' => new \DateTime('2016-01-10 18:48:00'),
			$ns . 'latestChildDateTime' => new \DateTime('2016-01-12 18:48:00'),
			$ns . 'objectType' => 'files',
			$ns . 'objectId' => '1848',
		];

		$this->comment->expects($this->once())
			->method('getId')
			->will($this->returnValue($expected[$ns . 'id']));

		$this->comment->expects($this->once())
			->method('getParentId')
			->will($this->returnValue($expected[$ns . 'parentId']));

		$this->comment->expects($this->once())
			->method('getTopmostParentId')
			->will($this->returnValue($expected[$ns . 'topmostParentId']));

		$this->comment->expects($this->once())
			->method('getChildrenCount')
			->will($this->returnValue($expected[$ns . 'childrenCount']));

		$this->comment->expects($this->once())
			->method('getMessage')
			->will($this->returnValue($expected[$ns . 'message']));

		$this->comment->expects($this->once())
			->method('getVerb')
			->will($this->returnValue($expected[$ns . 'verb']));

		$this->comment->expects($this->exactly(2))
			->method('getActorType')
			->will($this->returnValue($expected[$ns . 'actorType']));

		$this->comment->expects($this->exactly(2))
			->method('getActorId')
			->will($this->returnValue($expected[$ns . 'actorId']));

		$this->comment->expects($this->once())
			->method('getCreationDateTime')
			->will($this->returnValue($expected[$ns . 'creationDateTime']));

		$this->comment->expects($this->once())
			->method('getLatestChildDateTime')
			->will($this->returnValue($expected[$ns . 'latestChildDateTime']));

		$this->comment->expects($this->once())
			->method('getObjectType')
			->will($this->returnValue($expected[$ns . 'objectType']));

		$this->comment->expects($this->once())
			->method('getObjectId')
			->will($this->returnValue($expected[$ns . 'objectId']));

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue($expected[$ns . 'actorDisplayName']));

		$this->userManager->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$properties = $this->node->getProperties(null);

		foreach($properties as $name => $value) {
			$this->assertTrue(isset($expected[$name]));
			$this->assertSame($expected[$name], $value);
			unset($expected[$name]);
		}
		$this->assertTrue(empty($expected));
	}
}
