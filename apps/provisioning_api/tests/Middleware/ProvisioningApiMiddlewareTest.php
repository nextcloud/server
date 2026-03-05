<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Tests\Middleware;

use OCA\Provisioning_API\Middleware\Exceptions\NotSubAdminException;
use OCA\Provisioning_API\Middleware\ProvisioningApiMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use Test\TestCase;

class ProvisioningApiMiddlewareTest extends TestCase {

	/** @var IControllerMethodReflector|\PHPUnit\Framework\MockObject\MockObject */
	private $reflector;

	protected function setUp(): void {
		parent::setUp();

		$this->reflector = $this->createMock(IControllerMethodReflector::class);
	}

	public static function dataAnnotation(): array {
		return [
			[false, false, false, false, false],
			[false, false, true, false, false],
			[false, true, true, false, false],
			[true, false, false, false, true],
			[true, false, true, false, false],
			[true, true, false, false, false],
			[true, true, true, false, false],
			[false, false, false, true, false],
			[false, false, true, true, false],
			[false, true, true, true, false],
			[true, false, false, true, false],
			[true, false, true, true, false],
			[true, true, false, true, false],
			[true, true, true, true, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAnnotation')]
	public function testBeforeController(bool $subadminRequired, bool $isAdmin, bool $isSubAdmin, bool $hasSettingAuthorizationAnnotation, bool $shouldThrowException): void {
		$middleware = new ProvisioningApiMiddleware(
			$this->reflector,
			$isAdmin,
			$isSubAdmin
		);

		$this->reflector->method('hasAnnotation')
			->willReturnCallback(function ($annotation) use ($subadminRequired, $hasSettingAuthorizationAnnotation) {
				if ($annotation === 'NoSubAdminRequired') {
					return !$subadminRequired;
				}
				if ($annotation === 'AuthorizedAdminSetting') {
					return $hasSettingAuthorizationAnnotation;
				}
				return false;
			});

		try {
			$middleware->beforeController(
				$this->createMock(Controller::class),
				'myMethod'
			);
			$this->assertFalse($shouldThrowException);
		} catch (NotSubAdminException $e) {
			$this->assertTrue($shouldThrowException);
		}
	}

	public static function dataAfterException(): array {
		return [
			[new NotSubAdminException(), false],
			[new \Exception('test', 42), true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataAfterException')]
	public function testAfterException(\Exception $exception, bool $forwared): void {
		$middleware = new ProvisioningApiMiddleware(
			$this->reflector,
			false,
			false
		);

		try {
			$middleware->afterException(
				$this->createMock(Controller::class),
				'myMethod',
				$exception
			);
			$this->fail();
		} catch (OCSException $e) {
			$this->assertFalse($forwared);
			$this->assertSame($exception->getMessage(), $e->getMessage());
			$this->assertSame(Http::STATUS_FORBIDDEN, $e->getCode());
		} catch (\Exception $e) {
			$this->assertTrue($forwared);
			$this->assertSame($exception, $e);
		}
	}
}
