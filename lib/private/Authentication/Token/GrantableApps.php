<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OCP\INavigationManager;

/**
 * Apps an app password can be limited to as a whole, by app id
 */
class GrantableApps {
	/**
	 * AI apps, and apps whose access has scopes of its own
	 */
	private const EXCLUDED = ['assistant', 'calendar', 'contacts', 'files', 'tasks'];

	/**
	 * Apps that show other apps' data
	 */
	private const ACROSS_APPS = ['activity', 'dashboard'];

	public function __construct(
		private INavigationManager $navigationManager,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function get(): array {
		return array_column($this->entries(), 'id');
	}

	/**
	 * Apps in the current user's navigation, so restricted apps stay out. Apps that show other apps' data come first, then by name
	 *
	 * @return list<array{id: string, name: string, icon: string, acrossApps: bool}>
	 */
	public function entries(): array {
		$entries = [];
		foreach ($this->navigationManager->getAll(INavigationManager::TYPE_APPS) as $entry) {
			$app = $entry['app'] ?? $entry['id'];
			// An app's main entry wins over its extra ones, like the Tables contexts
			if (in_array($app, self::EXCLUDED, true) || (isset($entries[$app]) && $entry['id'] !== $app)) {
				continue;
			}
			$entries[$app] = ['id' => $app, 'name' => $entry['name'], 'icon' => $entry['icon'], 'acrossApps' => in_array($app, self::ACROSS_APPS, true)];
		}
		usort($entries, static fn (array $a, array $b): int => [$b['acrossApps'], strcasecmp($a['name'], $b['name'])] <=> [$a['acrossApps'], 0]);
		return $entries;
	}
}
