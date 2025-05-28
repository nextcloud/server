<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

/**
 * @group DB
 */
class BearerAuthTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IRequest&MockObject $request;
	private BearerAuth $bearerAuth;

	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);

		$this->bearerAuth = new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request,
			$this->config,
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
		/** @var RequestInterface&MockObject $request */
		$request = $this->createMock(RequestInterface::class);
		/** @var ResponseInterface&MockObject $response */
		$response = $this->createMock(ResponseInterface::class);
		$result = $this->bearerAuth->challenge($request, $response);
		$this->assertEmpty($result);
	}
}
