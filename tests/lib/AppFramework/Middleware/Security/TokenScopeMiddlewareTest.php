<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\Exceptions\TokenScopeException;
use OC\AppFramework\Middleware\Security\TokenScopeMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\TokenRoutes;
use OC\Authentication\Token\TokenScopes;
use OC\Lockdown\LockdownManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\AppFramework\Middleware\Security\Mock\SecurityMiddlewareController;
use Test\TestCase;

class TokenScopeMiddlewareTest extends TestCase {
	private const FILES = [TokenScopes::FILES_READ, TokenScopes::FILES_WRITE];
	private const CALENDAR = [TokenScopes::CALENDAR_READ, TokenScopes::CALENDAR_WRITE];
	private const USER = 'testAttributeNoAdminRequiredNoCSRFRequired';
	private const PUBLIC_PAGE = 'testAttributePublicPage';
	private const ADMIN_ONLY = 'testNoAnnotationNorAttribute';

	private IRequest&MockObject $request;
	private ControllerMethodReflector $reflector;
	private SecurityMiddlewareController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->reflector = new ControllerMethodReflector(Server::get(LoggerInterface::class));
		$this->controller = new SecurityMiddlewareController('test', $this->request);
	}

	public static function requests(): array {
		return [
			'files token, files api' => [self::FILES, 'files', 'ocs.files.api.getrecentfiles', self::USER, null, true],
			'files token, unlisted route' => [self::FILES, 'core', 'ocs.core.unifiedsearch.search', self::USER, null, false],
			'files token, admin-only route' => [self::FILES, 'files', 'ocs.files.filenames.getstatus', self::ADMIN_ONLY, null, false],
			'files token, share without files:share' => [self::FILES, 'files_sharing', 'ocs.files_sharing.shareapi.createshare', self::USER, null, false],
			'empty scopes, allowlisted route' => [[], 'core', 'core.wipe.checkwipe', self::PUBLIC_PAGE, null, true],
			'legacy token, admin-only route' => [null, 'files', 'ocs.files.filenames.getstatus', self::ADMIN_ONLY, null, true],
			'calendar token, export' => [self::CALENDAR, 'dav', 'ocs.dav.calendarexport.export', self::USER, null, true],
			'calendar token, export as itself' => [self::CALENDAR, 'dav', 'ocs.dav.calendarexport.export', self::USER, 'admin', true],
			'calendar token, export as someone else' => [self::CALENDAR, 'dav', 'ocs.dav.calendarexport.export', self::USER, 'alice', false],
		];
	}

	#[DataProvider('requests')]
	public function testBeforeController(?array $scopes, string $appId, string $route, string $method, ?string $user, bool $allowed): void {
		$this->request->method('getParam')->willReturnMap([['_route', null, $route], ['user', null, $user]]);
		$middleware = $this->middleware($appId, $scopes);
		$this->reflector->reflect($this->controller, $method);

		if (!$allowed) {
			$this->expectException(TokenScopeException::class);
		}
		$middleware->beforeController($this->controller, $method);
		$this->addToAssertionCount(1);
	}

	public function testPageRenderIsRefused(): void {
		$response = $this->middleware('files', self::FILES)
			->afterController($this->controller, self::USER, new TemplateResponse('files', 'index'));

		$this->assertNotInstanceOf(TemplateResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testOtherResponsesPass(): void {
		$json = new JSONResponse([]);
		$this->assertSame($json, $this->middleware('files', self::FILES)->afterController($this->controller, self::USER, $json));

		$page = new TemplateResponse('files', 'index');
		$this->assertSame($page, $this->middleware('files', null)->afterController($this->controller, self::USER, $page));
	}

	private function middleware(string $appId, ?array $scopes): TokenScopeMiddleware {
		$lockdownManager = new LockdownManager(fn () => $this->createMock(ISession::class), new TokenScopes());
		$token = new PublicKeyToken();
		$token->setScope($scopes === null ? [] : [TokenScopes::KEY => $scopes]);
		$lockdownManager->setToken($token);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		return new TokenScopeMiddleware(
			$this->request,
			$this->reflector,
			$lockdownManager,
			new TokenRoutes(),
			$userSession,
			$appId,
		);
	}
}
