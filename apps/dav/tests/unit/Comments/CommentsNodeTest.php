<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\Comments;

use OCA\DAV\Comments\CommentNode;
use OCP\Comments\IComment;
use OCP\Comments\MessageTooLongException;

class CommentsNodeTest extends \Test\TestCase {

	protected $commentsManager;
	protected $comment;
	protected $node;
	protected $userManager;
	protected $logger;
	protected $userSession;

	public function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMockBuilder('\OCP\Comments\ICommentsManager')
			->disableOriginalConstructor()
			->getMock();
		$this->comment = $this->getMockBuilder('\OCP\Comments\IComment')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();

		$this->node = new CommentNode(
			$this->commentsManager,
			$this->comment,
			$this->userManager,
			$this->userSession,
			$this->logger
		);
	}

	public function testDelete() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->once())
			->method('getId')
			->will($this->returnValue('19'));

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->once())
			->method('delete')
			->with('19');

		$this->node->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDeleteForbidden() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('mallory'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->never())
			->method('getId');

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->never())
			->method('delete');

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

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->once())
			->method('setMessage')
			->with($msg);

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->once())
			->method('save')
			->with($this->comment);

		$this->assertTrue($this->node->updateComment($msg));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage buh!
	 */
	public function testUpdateCommentLogException() {
		$msg = null;

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->once())
			->method('setMessage')
			->with($msg)
			->will($this->throwException(new \Exception('buh!')));

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->logger->expects($this->once())
			->method('logException');

		$this->node->updateComment($msg);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Message exceeds allowed character limit of
	 */
	public function testUpdateCommentMessageTooLongException() {
		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->once())
			->method('setMessage')
			->will($this->throwException(new MessageTooLongException()));

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->logger->expects($this->once())
			->method('logException');

		// imagine 'foo' has >1k characters. comment is mocked anyway.
		$this->node->updateComment('foo');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testUpdateForbiddenByUser() {
		$msg = 'HaXX0r';

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('mallory'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->never())
			->method('setMessage');

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->comment->expects($this->any())
			->method('getActorId')
			->will($this->returnValue('alice'));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->node->updateComment($msg);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testUpdateForbiddenByType() {
		$msg = 'HaXX0r';

		$user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->never())
			->method('getUID');

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->comment->expects($this->never())
			->method('setMessage');

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('bots'));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->node->updateComment($msg);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testUpdateForbiddenByNotLoggedIn() {
		$msg = 'HaXX0r';

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue(null));

		$this->comment->expects($this->never())
			->method('setMessage');

		$this->comment->expects($this->any())
			->method('getActorType')
			->will($this->returnValue('users'));

		$this->commentsManager->expects($this->never())
			->method('save');

		$this->node->updateComment($msg);
	}

	public function testPropPatch() {
		$propPatch = $this->getMockBuilder('Sabre\DAV\PropPatch')
			->disableOriginalConstructor()
			->getMock();

		$propPatch->expects($this->once())
			->method('handle')
			->with('{http://owncloud.org/ns}message');

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
			$ns . 'isUnread' => null,
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
			$this->assertTrue(array_key_exists($name, $expected));
			$this->assertSame($expected[$name], $value);
			unset($expected[$name]);
		}
		$this->assertTrue(empty($expected));
	}

	public function readCommentProvider() {
		$creationDT = new \DateTime('2016-01-19 18:48:00');
		$diff = new \DateInterval('PT2H');
		$readDT1 = clone $creationDT; $readDT1->sub($diff);
		$readDT2 = clone $creationDT; $readDT2->add($diff);
		return [
			[$creationDT, $readDT1, 'true'],
			[$creationDT, $readDT2, 'false'],
			[$creationDT, null, 'true'],
		];
	}

	/**
	 * @dataProvider readCommentProvider
	 * @param $expected
	 */
	public function testGetPropertiesUnreadProperty($creationDT, $readDT, $expected) {
		$this->comment->expects($this->any())
			->method('getCreationDateTime')
			->will($this->returnValue($creationDT));

		$this->commentsManager->expects($this->once())
			->method('getReadMark')
			->will($this->returnValue($readDT));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue(
				$this->getMockBuilder('\OCP\IUser')
					->disableOriginalConstructor()
					->getMock()
			));

		$properties = $this->node->getProperties(null);

		$this->assertTrue(array_key_exists(CommentNode::PROPERTY_NAME_UNREAD, $properties));
		$this->assertSame($properties[CommentNode::PROPERTY_NAME_UNREAD], $expected);
	}
}
