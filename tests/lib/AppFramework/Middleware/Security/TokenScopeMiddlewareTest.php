<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use NCU\AppFramework\Http\Attribute\TokenScope;
use OC\AppFramework\Http\Attributes\NoTokenScopeRequired;
use OC\AppFramework\Middleware\Security\Exceptions\TokenScopeException;
use OC\AppFramework\Middleware\Security\TokenScopeMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Authentication\Token\PublicKeyToken;
use OC\Authentication\Token\TokenScopes;
use OC\Core\Controller\PreviewController;
use OC\Lockdown\LockdownManager;
use OCA\DAV\Controller\DirectController;
use OCA\DAV\TokenScopes as DavTokenScopes;
use OCA\Files\Controller\ConversionApiController;
use OCA\Files\Controller\DirectEditingController;
use OCA\Files\Controller\DirectEditingViewController;
use OCA\Files\Controller\TemplateController;
use OCA\Files\TokenScopes as FilesTokenScopes;
use OCA\Files_Sharing\Controller\ShareAPIController;
use OCA\Files_Sharing\Controller\ShareesAPIController;
use OCA\Files_Sharing\TokenScopes as SharingTokenScopes;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class TokenScopeTestController extends Controller {
	#[NoAdminRequired]
	#[NoTokenScopeRequired]
	public function anyToken(): void {
	}

	#[NoAdminRequired]
	#[TokenScope(scope: SharingTokenScopes::SHARES_WRITE)]
	public function share(): void {
	}

	#[NoAdminRequired]
	public function unannotated(): void {
	}

	#[NoAdminRequired]
	#[TokenScope(scope: 'deck:boards:read')]
	public function deck(): void {
	}

	#[NoTokenScopeRequired]
	public function adminOnly(): void {
	}
}

#[TokenScope(scope: FilesTokenScopes::FILES_WRITE)]
class TokenScopeTestClassController extends Controller {
	#[NoAdminRequired]
	public function files(): void {
	}

	#[NoAdminRequired]
	#[TokenScope(scope: SharingTokenScopes::SHARES_WRITE)]
	public function share(): void {
	}

	#[NoAdminRequired]
	#[NoTokenScopeRequired]
	public function anyToken(): void {
	}
}

class TokenScopeMiddlewareTest extends TestCase {
	private const FILES = [FilesTokenScopes::FILES_READ, FilesTokenScopes::FILES_WRITE];
	private const CALENDAR = [DavTokenScopes::CALENDAR_READ, DavTokenScopes::CALENDAR_WRITE];

	private IRequest&MockObject $request;
	private ControllerMethodReflector $reflector;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->reflector = new ControllerMethodReflector(Server::get(LoggerInterface::class));
	}

	public static function requests(): array {
		return [
			'any token' => [[], TokenScopeTestController::class, 'anyToken', true],
			'scope missing' => [self::FILES, TokenScopeTestController::class, 'share', false],
			'scope held' => [[...self::FILES, SharingTokenScopes::SHARES_WRITE], TokenScopeTestController::class, 'share', true],
			'unannotated' => [self::FILES, TokenScopeTestController::class, 'unannotated', false],
			'unannotated, legacy token' => [null, TokenScopeTestController::class, 'unannotated', true],
			'admin only' => [self::FILES, TokenScopeTestController::class, 'adminOnly', false],
			'class attribute' => [self::FILES, TokenScopeTestClassController::class, 'files', true],
			'class attribute, other scope' => [self::CALENDAR, TokenScopeTestClassController::class, 'files', false],
			'method overrides class' => [self::FILES, TokenScopeTestClassController::class, 'share', false],
			'method opens what the class limits' => [self::CALENDAR, TokenScopeTestClassController::class, 'anyToken', true],
			'app requires its own scope' => [['deck:boards:read'], TokenScopeTestController::class, 'deck', true, 'deck'],
			'app requires another app\'s scope' => [[...self::FILES, SharingTokenScopes::SHARES_WRITE], TokenScopeTestController::class, 'share', false, 'deck'],
			'app opens itself to every token' => [[], TokenScopeTestController::class, 'anyToken', false, 'deck'],
		];
	}

	#[DataProvider('requests')]
	public function testBeforeController(?array $scopes, string $class, string $method, bool $allowed, ?string $thirdPartyApp = null): void {
		$controller = new $class('test', $this->request);
		$this->reflector->reflect($controller, $method);

		if (!$allowed) {
			$this->expectException(TokenScopeException::class);
		}
		$this->middleware($scopes, $thirdPartyApp)->beforeController($controller, $method);
		$this->addToAssertionCount(1);
	}

	public function testPageRenderIsRefused(): void {
		$response = $this->middleware(self::FILES)
			->afterController(new TokenScopeTestController('test', $this->request), 'anyToken', new TemplateResponse('files', 'index'));

		$this->assertNotInstanceOf(TemplateResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testOtherResponsesPass(): void {
		$controller = new TokenScopeTestController('test', $this->request);
		$json = new JSONResponse([]);
		$this->assertSame($json, $this->middleware(self::FILES)->afterController($controller, 'anyToken', $json));

		$page = new TemplateResponse('files', 'index');
		$this->assertSame($page, $this->middleware(null)->afterController($controller, 'anyToken', $page));
	}

	/**
	 * Without their own attribute these would fall back to their class's wider scope
	 */
	public static function pinnedScopes(): array {
		return [
			'create share' => [ShareAPIController::class, 'createShare', SharingTokenScopes::SHARES_WRITE],
			'update share' => [ShareAPIController::class, 'updateShare', SharingTokenScopes::SHARES_WRITE],
			'delete share' => [ShareAPIController::class, 'deleteShare', SharingTokenScopes::SHARES_WRITE],
			'send share email' => [ShareAPIController::class, 'sendShareEmail', SharingTokenScopes::SHARES_WRITE],
			'sharee search' => [ShareesAPIController::class, 'search', SharingTokenScopes::SHARES_WRITE],
			'recommended sharees' => [ShareesAPIController::class, 'findRecommended', SharingTokenScopes::SHARES_WRITE],
			'direct link' => [DirectController::class, 'getUrl', SharingTokenScopes::SHARES_WRITE],
			'preview' => [PreviewController::class, 'getPreview', FilesTokenScopes::FILES_READ],
			'preview by file id' => [PreviewController::class, 'getPreviewByFileId', FilesTokenScopes::FILES_READ],
		];
	}

	#[DataProvider('pinnedScopes')]
	public function testPinnedScopes(string $class, string $method, string $scope): void {
		$attribute = (new \ReflectionMethod($class, $method))->getAttributes(TokenScope::class)[0]
			?? (new \ReflectionClass($class))->getAttributes(TokenScope::class)[0];
		$this->assertSame($scope, $attribute->newInstance()->scope);
	}

	/**
	 * These hand files to other apps, so no scoped token may reach them
	 */
	public static function refusedControllers(): array {
		return [
			[TemplateController::class],
			[DirectEditingController::class],
			[DirectEditingViewController::class],
			[ConversionApiController::class],
		];
	}

	#[DataProvider('refusedControllers')]
	public function testRefusedControllersStayUnannotated(string $class): void {
		$class = new \ReflectionClass($class);
		foreach ([TokenScope::class, NoTokenScopeRequired::class] as $attribute) {
			$this->assertSame([], $class->getAttributes($attribute));
			foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
				$this->assertSame([], $method->getAttributes($attribute), $method->getName());
			}
		}
	}

	/**
	 * @param string|null $thirdPartyApp app the controller belongs to, null for a shipped app
	 */
	private function middleware(?array $scopes, ?string $thirdPartyApp = null): TokenScopeMiddleware {
		$lockdownManager = new LockdownManager(fn () => $this->createMock(ISession::class), new TokenScopes());
		$token = new PublicKeyToken();
		$token->setScope($scopes === null ? [] : [TokenScopes::KEY => $scopes]);
		$lockdownManager->setToken($token);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('isShipped')->willReturn($thirdPartyApp === null);

		return new TokenScopeMiddleware($this->reflector, $lockdownManager, $appManager, $thirdPartyApp ?? 'files');
	}
}
