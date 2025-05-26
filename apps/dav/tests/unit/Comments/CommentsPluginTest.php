<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Comments;

use OC\Comments\Comment;
use OCA\DAV\Comments\CommentsPlugin as CommentsPluginImplementation;
use OCA\DAV\Comments\EntityCollection;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\INode;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class CommentsPluginTest extends \Test\TestCase {
	private \Sabre\DAV\Server&MockObject $server;
	private Tree&MockObject $tree;
	private ICommentsManager&MockObject $commentsManager;
	private IUserSession&MockObject $userSession;
	private CommentsPluginImplementation $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->tree = $this->createMock(Tree::class);

		$this->server = $this->getMockBuilder(\Sabre\DAV\Server::class)
			->setConstructorArgs([$this->tree])
			->onlyMethods(['getRequestUri'])
			->getMock();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->plugin = new CommentsPluginImplementation($this->commentsManager, $this->userSession);
	}

	public function testCreateComment(): void {
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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->willReturn('alice');

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->willReturn('files');
		$node->expects($this->once())
			->method('getId')
			->willReturn('42');

		$node->expects($this->once())
			->method('setReadMarker')
			->with(null);

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->willReturn($comment);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn($node);

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->once())
			->method('getUrl')
			->willReturn('http://example.com/dav/' . $path);

		$response->expects($this->once())
			->method('setHeader')
			->with('Content-Location', 'http://example.com/dav/' . $path . '/23');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateCommentInvalidObject(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder(EntityCollection::class)
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

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

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
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateCommentInvalidActor(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->willReturn('files');
		$node->expects($this->once())
			->method('getId')
			->willReturn('42');

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
			->willReturn($node);

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateCommentUnsupportedMediaType(): void {
		$this->expectException(\Sabre\DAV\Exception\UnsupportedMediaType::class);

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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->never())
			->method('getUID');

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->willReturn('files');
		$node->expects($this->once())
			->method('getId')
			->willReturn('42');

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
			->willReturn($node);

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/trumpscript');

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateCommentInvalidPayload(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->willReturn('alice');

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->willReturn('files');
		$node->expects($this->once())
			->method('getId')
			->willReturn('42');

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->willReturn($comment);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn($node);

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$request->expects($this->never())
			->method('getUrl');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testCreateCommentMessageTooLong(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);
		$this->expectExceptionMessage('Message exceeds allowed character limit of');

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

		$user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())
			->method('getUID')
			->willReturn('alice');

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('getName')
			->willReturn('files');
		$node->expects($this->once())
			->method('getId')
			->willReturn('42');

		$node->expects($this->never())
			->method('setReadMarker');

		$this->commentsManager->expects($this->once())
			->method('create')
			->with('users', 'alice', 'files', '42')
			->willReturn($comment);

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		// technically, this is a shortcut. Inbetween EntityTypeCollection would
		// be returned, but doing it exactly right would not be really
		// unit-testing like, as it would require to haul in a lot of other
		// things.
		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn($node);

		$request = $this->getMockBuilder(RequestInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$response = $this->getMockBuilder(ResponseInterface::class)
			->disableOriginalConstructor()
			->getMock();

		$request->expects($this->once())
			->method('getPath')
			->willReturn('/' . $path);

		$request->expects($this->once())
			->method('getBodyAsString')
			->willReturn($requestData);

		$request->expects($this->once())
			->method('getHeader')
			->with('Content-Type')
			->willReturn('application/json');

		$response->expects($this->never())
			->method('setHeader');

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->httpPost($request, $response);
	}


	public function testOnReportInvalidNode(): void {
		$this->expectException(\Sabre\DAV\Exception\ReportNotSupported::class);

		$path = 'totally/unrelated/13';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn(
				$this->getMockBuilder(INode::class)
					->disableOriginalConstructor()
					->getMock()
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, [], '/' . $path);
	}


	public function testOnReportInvalidReportName(): void {
		$this->expectException(\Sabre\DAV\Exception\ReportNotSupported::class);

		$path = 'comments/files/42';

		$this->tree->expects($this->any())
			->method('getNodeForPath')
			->with('/' . $path)
			->willReturn(
				$this->getMockBuilder(INode::class)
					->disableOriginalConstructor()
					->getMock()
			);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->plugin->initialize($this->server);

		$this->plugin->onReport('{whoever}whatever', [], '/' . $path);
	}

	public function testOnReportDateTimeEmpty(): void {
		$path = 'comments/files/42';

		$parameters = [
			[
				'name' => '{http://owncloud.org/ns}limit',
				'value' => 5,
			],
			[
				'name' => '{http://owncloud.org/ns}offset',
				'value' => 10,
			],
			[
				'name' => '{http://owncloud.org/ns}datetime',
				'value' => '',
			]
		];

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('findChildren')
			->with(5, 10, null)
			->willReturn([]);

		$response = $this->getMockBuilder(ResponseInterface::class)
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
			->willReturn($node);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, $parameters, '/' . $path);
	}

	public function testOnReport(): void {
		$path = 'comments/files/42';

		$parameters = [
			[
				'name' => '{http://owncloud.org/ns}limit',
				'value' => 5,
			],
			[
				'name' => '{http://owncloud.org/ns}offset',
				'value' => 10,
			],
			[
				'name' => '{http://owncloud.org/ns}datetime',
				'value' => '2016-01-10 18:48:00',
			]
		];

		$node = $this->getMockBuilder(EntityCollection::class)
			->disableOriginalConstructor()
			->getMock();
		$node->expects($this->once())
			->method('findChildren')
			->with(5, 10, new \DateTime($parameters[2]['value']))
			->willReturn([]);

		$response = $this->getMockBuilder(ResponseInterface::class)
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
			->willReturn($node);

		$this->server->expects($this->any())
			->method('getRequestUri')
			->willReturn($path);
		$this->server->httpResponse = $response;
		$this->plugin->initialize($this->server);

		$this->plugin->onReport(CommentsPluginImplementation::REPORT_NAME, $parameters, '/' . $path);
	}
}
