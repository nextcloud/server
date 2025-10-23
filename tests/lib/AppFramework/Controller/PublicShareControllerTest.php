<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Controller;

use OCP\AppFramework\PublicShareController;
use OCP\IRequest;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;

class TestController extends PublicShareController {
	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		private string $hash,
		private bool $isProtected,
	) {
		parent::__construct($appName, $request, $session);
	}

	protected function getPasswordHash(): string {
		return $this->hash;
	}

	public function isValidToken(): bool {
		return false;
	}

	protected function isPasswordProtected(): bool {
		return $this->isProtected;
	}
}

class PublicShareControllerTest extends \Test\TestCase {
	private IRequest&MockObject $request;
	private ISession&MockObject $session;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->session = $this->createMock(ISession::class);
	}

	public function testGetToken(): void {
		$controller = new TestController('app', $this->request, $this->session, 'hash', false);

		$controller->setToken('test');
		$this->assertEquals('test', $controller->getToken());
	}

	public static function dataIsAuthenticated(): array {
		return [
			[false, 'token1', 'token1', 'hash1', 'hash1',  true],
			[false, 'token1', 'token1', 'hash1', 'hash2',  true],
			[false, 'token1', 'token2', 'hash1', 'hash1',  true],
			[false, 'token1', 'token2', 'hash1', 'hash2',  true],
			[ true, 'token1', 'token1', 'hash1', 'hash1',  true],
			[ true, 'token1', 'token1', 'hash1', 'hash2', false],
			[ true, 'token1', 'token2', 'hash1', 'hash1', false],
			[ true, 'token1', 'token2', 'hash1', 'hash2', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsAuthenticated')]
	public function testIsAuthenticatedNotPasswordProtected(bool $protected, string $token1, string $token2, string $hash1, string $hash2, bool $expected): void {
		$controller = new TestController('app', $this->request, $this->session, $hash2, $protected);

		$this->session->method('get')
			->with(PublicShareController::DAV_AUTHENTICATED_FRONTEND)
			->willReturn("{\"$token1\":\"$hash1\"}");

		$controller->setToken($token2);

		$this->assertEquals($expected, $controller->isAuthenticated());
	}
}
