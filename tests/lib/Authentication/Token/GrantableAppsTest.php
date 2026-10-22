<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Token\GrantableApps;
use OCP\INavigationManager;
use Test\TestCase;

class GrantableAppsTest extends TestCase {
	public function testNavigationAppsWithoutAggregatorsAndCapabilities(): void {
		$navigationManager = $this->createMock(INavigationManager::class);
		$navigationManager->method('getAll')->with(INavigationManager::TYPE_APPS)->willReturn([
			'files' => ['id' => 'files', 'app' => 'files'],
			'deck' => ['id' => 'deck', 'app' => 'deck'],
			'dashboard' => ['id' => 'dashboard', 'app' => 'dashboard'],
			'calendar' => ['id' => 'calendar', 'app' => 'calendar'],
			'tables_application_3' => ['id' => 'tables_application_3', 'app' => 'tables'],
			'tables_application_4' => ['id' => 'tables_application_4', 'app' => 'tables'],
			'spreed' => ['id' => 'spreed'],
		]);

		$this->assertSame(['deck', 'tables', 'spreed'], (new GrantableApps($navigationManager))->get());
	}
}
