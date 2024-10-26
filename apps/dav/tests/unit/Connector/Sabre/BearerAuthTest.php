<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * @group DB
 */
class BearerAuthTest extends TestCase {
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var ISession|\PHPUnit\Framework\MockObject\MockObject */
	private $session;
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var BearerAuth */
	private $bearerAuth;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);

		$this->bearerAuth = new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request
		);
	}

	public function testValidateBearerTokenNotLoggedIn(): void {
		$this->assertFalse($this->bearerAuth->validateBearerToken('Token'));
	}

	public function testValidateBearerToken(): void {
		$this->userSession
			->expects($this->exactly(2))
			->method('isLoggedIn')
			->willReturnOnConsecutiveCalls(
				false,
				true,
			);
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('admin');
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->assertSame('principals/users/admin', $this->bearerAuth->validateBearerToken('Token'));
	}

	public function testChallenge(): void {
		/** @var \PHPUnit\Framework\MockObject\MockObject|RequestInterface $request */
		$request = $this->createMock(RequestInterface::class);
		/** @var \PHPUnit\Framework\MockObject\MockObject|ResponseInterface $response */
		$response = $this->createMock(ResponseInterface::class);
		$result = $this->bearerAuth->challenge($request, $response);
		$this->assertEmpty($result);
	}
}
