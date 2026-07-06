<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OAuth2\Tests\Controller;

use OCA\OAuth2\Controller\SettingsController;
use OCP\AppFramework\Http;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
class SettingsControllerTest extends TestCase {
	public function testInvalidRedirectUri(): void {
		$settingsController = Server::get(SettingsController::class);
		$result = $settingsController->addClient('test', 'invalidurl');

		$this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame(['message' => 'Your redirect URL needs to be a full URL for example: https://yourdomain.com/path'], $result->getData());
	}
}
