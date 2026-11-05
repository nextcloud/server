<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCA\Settings\Controller\DeclarativeSettingsController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Settings\IDeclarativeManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class DeclarativeSettingsControllerTest extends TestCase {
	private IDeclarativeManager&MockObject $declarativeManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private DeclarativeSettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->declarativeManager = $this->createMock(IDeclarativeManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->controller = new DeclarativeSettingsController(
			'settings',
			$this->createMock(IRequest::class),
			$userSession,
			$this->declarativeManager,
			$this->createMock(LoggerInterface::class),
			$this->eventDispatcher,
		);
	}

	public static function dataSetValue(): array {
		return [
			'plain value' => ['setValue'],
			'sensitive value' => ['setSensitiveValue'],
		];
	}

	#[DataProvider('dataSetValue')]
	public function testSetValueIsAuditedWithoutValue(string $method): void {
		$this->declarativeManager->expects($this->once())
			->method('setValue');

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'settings.declarative_value.updated'
				&& $event->getParameters() === ['appId' => 'myapp', 'formId' => 'myform', 'fieldId' => 'myfield']
				&& $event->getLogMessage() === 'Declarative setting of app "%s", form "%s", field "%s" was changed'));

		$this->controller->$method('myapp', 'myform', 'myfield', 'secret');
	}

	public function testFailedSetValueIsNotAudited(): void {
		$this->declarativeManager->method('setValue')
			->willThrowException(new \Exception('Unknown field'));
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->expectException(OCSBadRequestException::class);
		$this->controller->setValue('myapp', 'myform', 'myfield', 'value');
	}

	public function testUnauthorizedSetValueIsNotAudited(): void {
		$this->declarativeManager->method('setValue')
			->willThrowException(new NotAdminException('Not an admin'));
		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->expectException(NotAdminException::class);
		$this->controller->setValue('myapp', 'myform', 'myfield', 'value');
	}
}
