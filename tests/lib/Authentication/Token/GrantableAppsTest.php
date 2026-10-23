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
	public function testNavigationAppsWithoutCapabilitiesAndAi(): void {
		$navigationManager = $this->createMock(INavigationManager::class);
		$navigationManager->method('getAll')->with(INavigationManager::TYPE_APPS)->willReturn([
			'files' => ['id' => 'files', 'app' => 'files', 'name' => 'Files', 'icon' => 'files.svg'],
			'deck' => ['id' => 'deck', 'app' => 'deck', 'name' => 'Deck', 'icon' => 'deck.svg'],
			'dashboard' => ['id' => 'dashboard', 'app' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'dashboard.svg'],
			'calendar' => ['id' => 'calendar', 'app' => 'calendar', 'name' => 'Calendar', 'icon' => 'calendar.svg'],
			'assistant' => ['id' => 'assistant', 'app' => 'assistant', 'name' => 'Assistant', 'icon' => 'assistant.svg'],
			'tables_application_3' => ['id' => 'tables_application_3', 'app' => 'tables', 'name' => 'Inventory', 'icon' => 'inventory.svg'],
			'tables' => ['id' => 'tables', 'app' => 'tables', 'name' => 'Tables', 'icon' => 'tables.svg'],
			'spreed' => ['id' => 'spreed', 'name' => 'Talk', 'icon' => 'talk.svg'],
			'activity' => ['id' => 'activity', 'app' => 'activity', 'name' => 'Activity', 'icon' => 'activity.svg'],
		]);
		$grantableApps = new GrantableApps($navigationManager);

		$this->assertSame([
			['id' => 'activity', 'name' => 'Activity', 'icon' => 'activity.svg', 'acrossApps' => true],
			['id' => 'dashboard', 'name' => 'Dashboard', 'icon' => 'dashboard.svg', 'acrossApps' => true],
			['id' => 'deck', 'name' => 'Deck', 'icon' => 'deck.svg', 'acrossApps' => false],
			['id' => 'tables', 'name' => 'Tables', 'icon' => 'tables.svg', 'acrossApps' => false],
			['id' => 'spreed', 'name' => 'Talk', 'icon' => 'talk.svg', 'acrossApps' => false],
		], $grantableApps->entries());
		$this->assertSame(['activity', 'dashboard', 'deck', 'tables', 'spreed'], $grantableApps->get());
	}
}
