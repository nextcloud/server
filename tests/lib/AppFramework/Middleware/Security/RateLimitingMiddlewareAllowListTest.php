<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware\Security;

use OC\AppFramework\Middleware\Security\RateLimitingMiddleware;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\Security\Ip\BruteforceAllowList;
use OC\Security\RateLimiting\Limiter;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Test\AppFramework\Middleware\Security\Mock\RateLimitingMiddlewareController;

class RateLimitingMiddlewareAllowListTest extends TestCase {
	public static function allowListProvider(): iterable {
		$requests = [
			'anonymous annotation' => [false, 'testMethodWithAnnotationFallback', false],
			'authenticated annotation fallback' => [true, 'testMethodWithAnnotationFallback', false],
			'authenticated annotation user limit' => [true, 'testMethodWithAnnotation', true],
			'anonymous attribute' => [false, 'testMethodWithAttributesFallback', false],
			'authenticated attribute fallback' => [true, 'testMethodWithAttributesFallback', false],
			'authenticated attribute user limit' => [true, 'testMethodWithAttributes', true],
		];
		$settings = [
			'enabled, listed' => [true, true, true],
			'enabled, unlisted' => [true, false, false],
			'disabled, listed' => [false, true, false],
			'disabled, unlisted' => [false, false, false],
		];

		foreach ($requests as $requestName => $request) {
			foreach ($settings as $settingName => $setting) {
				yield $requestName . ': ' . $settingName => [...$request, ...$setting];
			}
		}
	}

	#[DataProvider('allowListProvider')]
	public function testAllowList(
		bool $loggedIn,
		string $methodName,
		bool $userLimit,
		bool $allowListEnabled,
		bool $allowListed,
		bool $shouldBypass,
	): void {
		$request = $this->createMock(IRequest::class);
		$request->method('getRemoteAddress')->willReturn('192.0.2.10');
		$user = $this->createMock(IUser::class);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('isLoggedIn')->willReturn($loggedIn);
		$userSession->method('getUser')->willReturn($user);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')
			->with('bruteforcesettings', 'apply_allowlist_to_ratelimit')
			->willReturn($allowListEnabled);
		$allowList = $this->createMock(BruteforceAllowList::class);
		$allowList->method('isBypassListed')
			->with('192.0.2.10')
			->willReturn($allowListed);
		$limiter = $this->createMock(Limiter::class);
		$controller = new RateLimitingMiddlewareController('test', $request);
		$identifier = $controller::class . '::' . $methodName;
		$limiter->expects(!$shouldBypass && $userLimit ? $this->once() : $this->never())
			->method('registerUserRequest')
			->with($identifier, 20, 200, $user);
		$limiter->expects(!$shouldBypass && !$userLimit ? $this->once() : $this->never())
			->method('registerAnonRequest')
			->with($identifier, 10, 100, '192.0.2.10');
		$logger = $this->createMock(LoggerInterface::class);
		$reflector = new ControllerMethodReflector($logger);
		$reflector->reflect($controller, $methodName);
		$middleware = new RateLimitingMiddleware(
			$request,
			$userSession,
			$reflector,
			$limiter,
			$this->createMock(ISession::class),
			$appConfig,
			$this->createMock(IConfig::class),
			$allowList,
			$logger,
		);

		$middleware->beforeController($controller, $methodName);
	}
}
