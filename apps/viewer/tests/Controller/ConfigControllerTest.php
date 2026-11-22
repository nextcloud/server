<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Tests\Controller;

use OCA\Viewer\Controller\ConfigController;
use OCA\Viewer\Service\UserConfig;
use OCP\AppFramework\Http;
use Test\TestCase;

class ConfigControllerTest extends TestCase {
	private ConfigController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->controller = $this->createInstanceWithMocks(ConfigController::class, ['appName' => 'viewer']);
	}

	public function testSavesTheSetting(): void {
		$this->getAutoMock(UserConfig::class)->expects($this->once())
			->method('setConfig')
			->with('slideshow_delay', 10);

		$response = $this->controller->setConfig('slideshow_delay', 10);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame(['key' => 'slideshow_delay', 'value' => 10], $response->getData());
	}

	public function testAnswersBadRequestForWhatTheSettingRefuses(): void {
		$this->getAutoMock(UserConfig::class)->method('setConfig')
			->willThrowException(new \InvalidArgumentException('Invalid config value'));

		$response = $this->controller->setConfig('slideshow_delay', 0);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertSame(['message' => 'Invalid config value'], $response->getData());
	}
}
