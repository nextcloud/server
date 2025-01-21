<?php
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
use OCP\IL10N;

/**
 * Verifies whether an user has at least subadmin rights.
 * To bypass use the `@NoSubAdminRequired` annotation
 *
 * @package Tests\Settings\Middleware
 */
class SubadminMiddlewareTest extends \Test\TestCase {
	/** @var SubadminMiddleware */
	private $subadminMiddlewareAsSubAdmin;
	/** @var SubadminMiddleware */
	private $subadminMiddleware;
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var Controller */
	private $controller;
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->reflector = $this->getMockBuilder(ControllerMethodReflector::class)
			->disableOriginalConstructor()->getMock();
		$this->controller = $this->getMockBuilder(Controller::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->createMock(IL10N::class);

		$this->subadminMiddlewareAsSubAdmin = new SubadminMiddleware($this->reflector, true, $this->l10n);
		$this->subadminMiddleware = new SubadminMiddleware($this->reflector, false, $this->l10n);
	}


	public function testBeforeControllerAsUserWithExemption(): void {
		$this->expectException(NotAdminException::class);

		$this->reflector
			->expects($this->exactly(2))
			->method('hasAnnotation')
			->withConsecutive(
				['NoSubAdminRequired'],
				['AuthorizedAdminSetting'],
			)->willReturn(false);
		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}


	public function testBeforeControllerAsUserWithoutExemption(): void {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubAdminRequired')
			->willReturn(true);
		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}

	public function testBeforeControllerAsSubAdminWithoutExemption(): void {
		$this->reflector
			->expects($this->exactly(2))
			->method('hasAnnotation')
			->withConsecutive(
				['NoSubAdminRequired'],
				['AuthorizedAdminSetting'],
			)->willReturn(false);
		$this->subadminMiddlewareAsSubAdmin->beforeController($this->controller, 'foo');
	}

	public function testBeforeControllerAsSubAdminWithExemption(): void {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubAdminRequired')
			->willReturn(true);
		$this->subadminMiddlewareAsSubAdmin->beforeController($this->controller, 'foo');
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
