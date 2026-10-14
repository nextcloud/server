<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OCA\Settings\Controller\AISettingsController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AISettingsControllerTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IEventDispatcher&MockObject $eventDispatcher;
	private AISettingsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->controller = new AISettingsController(
			'settings',
			$this->createMock(IRequest::class),
			'admin',
			$this->appConfig,
			$this->eventDispatcher,
		);
	}

	public function testUpdateAuditsChangedKeys(): void {
		$this->appConfig->method('setValueString')
			->willReturnCallback(fn (string $app, string $key): bool => $key === 'ai.stt_provider');

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'settings.ai.updated'
				&& $event->getParameters() === ['userId' => 'admin', 'configKey' => 'ai.stt_provider', 'configValue' => '"provider"']
				&& $event->getLogMessage() === 'AI configuration was changed by user %s: %s was set to %s'));

		$this->controller->update([
			'ai.stt_provider' => 'provider',
			'ai.text2image_provider' => 'unchanged',
		]);
	}
}
