<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Middleware;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCA\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Group\ISubAdmin;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Verifies whether an user has at least subadmin rights.
 * To bypass use the `@NoSubAdminRequired` annotation
 *
 * @package Tests\Settings\Middleware
 */
class SubadminMiddlewareTest extends \Test\TestCase {
	private SubadminMiddleware $subadminMiddleware;
	private IUserSession&MockObject $userSession;
	private ISubAdmin&MockObject $subAdminManager;
	private ControllerMethodReflector&MockObject $reflector;
	private Controller&MockObject $controller;
	private IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->reflector = $this->createMock(ControllerMethodReflector::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->subAdminManager = $this->createMock(ISubAdmin::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->subadminMiddleware = new SubadminMiddleware(
			$this->reflector,
			$this->userSession,
			$this->subAdminManager,
			$this->l10n,
		);

		$this->controller = $this->createMock(Controller::class);

		$this->userSession
			->expects(self::any())
			->method('getUser')
			->willReturn($this->createMock(IUser::class));
	}


	public function testBeforeControllerAsUserWithoutAnnotation(): void {
		$this->expectException(NotAdminException::class);

		$this->reflector
			->expects($this->exactly(2))
			->method('hasAnnotation')
			->willReturnMap([
				['NoSubAdminRequired', false],
				['AuthorizedAdminSetting', false],
			]);

		$this->subAdminManager
			->expects(self::once())
			->method('isSubAdmin')
			->willReturn(false);

		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}


	public function testBeforeControllerWithAnnotation(): void {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubAdminRequired')
			->willReturn(true);

		$this->subAdminManager
			->expects(self::never())
			->method('isSubAdmin');

		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}

	public function testBeforeControllerAsSubAdminWithoutAnnotation(): void {
		$this->reflector
			->expects($this->exactly(2))
			->method('hasAnnotation')
			->willReturnMap([
				['NoSubAdminRequired', false],
				['AuthorizedAdminSetting', false],
			]);

		$this->subAdminManager
			->expects(self::once())
			->method('isSubAdmin')
			->willReturn(true);

		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}

	public function testAfterNotAdminException(): void {
		$expectedResponse = new TemplateResponse('core', '403', [], 'guest');
		$expectedResponse->setStatus(403);
		$this->assertEquals($expectedResponse, $this->subadminMiddleware->afterException($this->controller, 'foo', new NotAdminException('')));
	}


	public function testAfterRegularException(): void {
		$this->expectException(\Exception::class);

		$expectedResponse = new TemplateResponse('core', '403', [], 'guest');
		$expectedResponse->setStatus(403);
		$this->subadminMiddleware->afterException($this->controller, 'foo', new \Exception());
	}
}
