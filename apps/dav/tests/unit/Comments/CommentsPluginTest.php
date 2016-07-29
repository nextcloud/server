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

use OC\Comments\Comment;
use OCA\DAV\Comments\CommentsPlugin as CommentsPluginImplementation;
use OCP\Comments\IComment;
use Sabre\DAV\Exception\NotFound;

class CommentsPluginTest extends \Test\TestCase {
	/** @var \Sabre\DAV\Server */
	private $server;

	/** @var \Sabre\DAV\Tree */
	private $tree;

	/** @var \OCP\Comments\ICommentsManager */
	private $commentsManager;

	/** @var  \OCP\IUserSession */
	private $userSession;

	/** @var CommentsPluginImplementation */
	private $plugin;

	public function setUp() {
		parent::setUp();
		$this->tree = $this->getMockBuilder('\Sabre\DAV\Tree')
			->disableOriginalConstructor()
			->getMock();

		$this->server = $this->getMockBuilder('\Sabre\DAV\Server')
			->setConstructorArgs([$this->tree])
			->setMethods(['getRequestUri'])
			->getMock();

		$this->commentsManager = $this->getMockBuilder('\OCP\Comments\ICommentsManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->plugin = new CommentsPluginImplementation($this->commentsManager, $this->userSession);
	}

	public function testCreateComment() {
		$commentData = [
			'actorType' => 'users',
			'verb' => 'comment',
			'message' => 'my first comment',
		];

		$comment = new Comment([
			'objectType' => 'files',
			'objectId' => '42',
			'actorType' => 'users',
			'actorId' => 'alice'
		] + $commentData);
		$comment->setId('23');

		$path = 'comments/files/42';

		$requestData = json_encode($commentData);

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$node->expects($this->once())
			->method('setReadMarker')
			->with(null);

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->will($this->returnValue($comment));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$request->expects($this->once())
			->method('getUrl')
			->will($this->returnValue('http://example.com/dav/' . $path));

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/' . $path . '/23');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testCreateCommentInvalidObject() {
		$commentData = [
			'actorType' => 'users',
			'verb' => 'comment',
			'message' => 'my first comment',
		];

		$comment = new Comment([
				'objectType' => 'files',
				'objectId' => '666',
				'actorType' => 'users',
				'actorId' => 'alice'
			] + $commentData);
		$comment->setId('23');

		$path = 'comments/files/666';

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->never())
			->method('getName');
		$node->expects($this->never())
			->method('getId');

		$this->commentsManager->expects($this->never())
			->method('create');

		$this->userSession->expects($this->never())
			->method('getUser');

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->throwException(new \Sabre\DAV\Exception\NotFound()));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->never())
			->method('getBodyAsString');

		$request->expects($this->never())
			->method('getHeader')
			->with('Content-Type');

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testCreateCommentInvalidActor() {
		$commentData = [
			'actorType' => 'robots',
			'verb' => 'comment',
			'message' => 'my first comment',
		];

		$comment = new Comment([
				'objectType' => 'files',
				'objectId' => '42',
				'actorType' => 'users',
				'actorId' => 'alice'
			] + $commentData);
		$comment->setId('23');

		$path = 'comments/files/42';

		$requestData = json_encode($commentData);

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$this->commentsManager->expects($this->never())
			->method('create');

		$this->userSession->expects($this->never())
			->method('getUser');

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\UnsupportedMediaType
	 */
	public function testCreateCommentUnsupportedMediaType() {
		$commentData = [
			'actorType' => 'users',
			'verb' => 'comment',
			'message' => 'my first comment',
		];

		$comment = new Comment([
				'objectType' => 'files',
				'objectId' => '42',
				'actorType' => 'users',
				'actorId' => 'alice'
			] + $commentData);
		$comment->setId('23');

		$path = 'comments/files/42';

		$requestData = json_encode($commentData);

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$this->commentsManager->expects($this->never())
			->method('create');

		$this->userSession->expects($this->never())
			->method('getUser');

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/trumpscript'));

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testCreateCommentInvalidPayload() {
		$commentData = [
			'actorType' => 'users',
			'verb' => '',
			'message' => '',
		];

		$comment = new Comment([
				'objectType' => 'files',
				'objectId' => '42',
				'actorType' => 'users',
				'actorId' => 'alice',
				'message' => 'dummy',
				'verb' => 'dummy'
			]);
		$comment->setId('23');

		$path = 'comments/files/42';

		$requestData = json_encode($commentData);

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->will($this->returnValue($comment));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 * @expectedExceptionMessage Message exceeds allowed character limit of
	 */
	public function testCreateCommentMessageTooLong() {
		$commentData = [
			'actorType' => 'users',
			'verb' => 'comment',
			'message' => str_pad('', IComment::MAX_MESSAGE_LENGTH + 1, 'x'),
		];

		$comment = new Comment([
				'objectType' => 'files',
				'objectId' => '42',
				'actorType' => 'users',
				'actorId' => 'alice',
				'verb' => 'comment',
			]);
		$comment->setId('23');

		$path = 'comments/files/42';

		$requestData = json_encode($commentData);

		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('alice'));

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->will($this->returnValue('files'));
		$node->expects($this->once())
			->method('getId')
			->will($this->returnValue('42'));

		$node->expects($this->never())
			->method('setReadMarker');

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->will($this->returnValue($comment));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$request = $this->getMockBuilder('Sabre\HTTP\RequestInterface')
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->will($this->returnValue('/' . $path));

		$request->expects($this->once())
			->method('getBodyAsString')
			->will($this->returnValue($requestData));

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->will($this->returnValue('application/json'));

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ReportNotSupported
	 */
	public function testOnReportInvalidNode() {
		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder('\Sabre\DAV\INode')
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, [], '/' . $path);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ReportNotSupported
	 */
	public function testOnReportInvalidReportName() {
		$path = 'comments/files/42';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue(
				$this->getMockBuilder('\Sabre\DAV\INode')
					->disableOriginalConstructor()
					->getMock()
			));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->plugin->initialize($this->server);

		$this->plugin->onReport('{whoever}whatever', [], '/' . $path);
	}

	public function testOnReportDateTimeEmpty() {
		$path = 'comments/files/42';

		$parameters = [
			[
				'name'  => '{http://owncloud.org/ns}limit',
				'value' => 5,
			],
			[
				'name'  => '{http://owncloud.org/ns}offset',
				'value' => 10,
			],
			[
				'name' => '{http://owncloud.org/ns}datetime',
				'value' => '',
			]
		];

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('findChildren')
			->with(5, 10, null)
			->will($this->returnValue([]));

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Type', 'application/xml; charset=utf-8');

		$response->expects($this->once())
			->method('setStatus')
			->with(207);

		$response->expects($this->once())
			->method('setBody');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, $parameters, '/' . $path);
	}

	public function testOnReport() {
		$path = 'comments/files/42';

		$parameters = [
			[
				'name'  => '{http://owncloud.org/ns}limit',
				'value' => 5,
			],
			[
				'name'  => '{http://owncloud.org/ns}offset',
				'value' => 10,
			],
			[
				'name' => '{http://owncloud.org/ns}datetime',
				'value' => '2016-01-10 18:48:00',
			]
		];

		$node = $this->getMockBuilder('\OCA\DAV\Comments\EntityCollection')
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('findChildren')
			->with(5, 10, new \DateTime($parameters[2]['value']))
			->will($this->returnValue([]));

		$response = $this->getMockBuilder('Sabre\HTTP\ResponseInterface')
			->disableOriginalConstructor()
			->getMock();

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Type', 'application/xml; charset=utf-8');

		$response->expects($this->once())
			->method('setStatus')
			->with(207);

		$response->expects($this->once())
			->method('setBody');

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->will($this->returnValue($node));

		$this->server->expects($this->any())
			->method('getRequestUri')
			->will($this->returnValue($path));
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, $parameters, '/' . $path);
	}



}
