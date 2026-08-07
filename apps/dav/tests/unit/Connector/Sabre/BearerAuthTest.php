<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\User\Session;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class BearerAuthTest extends TestCase {
	private IUserSession&MockObject $userSession;
	private ISession&MockObject $session;
	private IRequest&MockObject $request;
	private BearerAuth $bearerAuth;
	private ISetupManager&MockObject $setupManager;

	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(Session::class);
		$this->session = $this->createMock(ISession::class);
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->setupManager = $this->createMock(ISetupManager::class);

		$this->bearerAuth = new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request,
			$this->config,
			$this->setupManager,
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

	public function testValidateBearerTokenDefaultsOcmFlagToFalse(): void {
		$this->userSession
			->method('isLoggedIn')
			->willReturnOnConsecutiveCalls(false, false);
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request, false);

		$this->assertFalse($this->bearerAuth->validateBearerToken('Token'));
	}

	public function testValidateBearerTokenPassesOcmFlagWhenAllowed(): void {
		$bearerAuth = new BearerAuth(
			$this->userSession,
			$this->session,
			$this->request,
			$this->config,
			$this->setupManager,
			allowOcmAccessToken: true,
		);
		$this->userSession
			->method('isLoggedIn')
			->willReturnOnConsecutiveCalls(false, true);
		$this->userSession
			->expects($this->once())
			->method('tryTokenLogin')
			->with($this->request, true);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);

		$this->assertSame('principals/users/admin', $bearerAuth->validateBearerToken('Token'));
	}

	public function testChallenge(): void {
		$request = $this->createMock(RequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$this->bearerAuth->challenge($request, $response);
		$this->assertTrue(true);
	}
}
