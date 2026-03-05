<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\AdditionalScriptsMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\PublicShareController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;

class AdditionalScriptsMiddlewareTest extends \Test\TestCase {
	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var Controller */
	private $controller;

	/** @var AdditionalScriptsMiddleware */
	private $middleWare;
	/** @var IEventDispatcher|MockObject */
	private $dispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->userSession = $this->createMock(IUserSession::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->middleWare = new AdditionalScriptsMiddleware(
			$this->userSession,
			$this->dispatcher
		);

		$this->controller = $this->createMock(Controller::class);
	}

	public function testNoTemplateResponse(): void {
		$this->userSession->expects($this->never())
			->method($this->anything());
		$this->dispatcher->expects($this->never())
			->method($this->anything());

		$this->middleWare->afterController($this->controller, 'myMethod', $this->createMock(Response::class));
	}

	public function testPublicShareController(): void {
		$this->userSession->expects($this->never())
			->method($this->anything());
		$this->dispatcher->expects($this->never())
			->method($this->anything());

		$this->middleWare->afterController($this->createMock(PublicShareController::class), 'myMethod', $this->createMock(Response::class));
	}

	public function testStandaloneTemplateResponse(): void {
		$this->userSession->expects($this->never())
			->method($this->anything());
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event): void {
				if ($event instanceof BeforeTemplateRenderedEvent && $event->isLoggedIn() === false) {
					return;
				}

				$this->fail('Wrong event dispatched');
			});

		$this->middleWare->afterController($this->controller, 'myMethod', $this->createMock(StandaloneTemplateResponse::class));
	}

	public function testTemplateResponseNotLoggedIn(): void {
		$this->userSession->method('isLoggedIn')
			->willReturn(false);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event): void {
				if ($event instanceof BeforeTemplateRenderedEvent && $event->isLoggedIn() === false) {
					return;
				}

				$this->fail('Wrong event dispatched');
			});

		$this->middleWare->afterController($this->controller, 'myMethod', $this->createMock(TemplateResponse::class));
	}

	public function testTemplateResponseLoggedIn(): void {
		$events = [];

		$this->userSession->method('isLoggedIn')
			->willReturn(true);
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event): void {
				if ($event instanceof BeforeTemplateRenderedEvent && $event->isLoggedIn() === true) {
					return;
				}

				$this->fail('Wrong event dispatched');
			});

		$this->middleWare->afterController($this->controller, 'myMethod', $this->createMock(TemplateResponse::class));
	}
}
