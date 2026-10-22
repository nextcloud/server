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
	 * Apps that show other apps' data, and apps whose access has scopes of its own
	 */
	private const EXCLUDED = ['activity', 'assistant', 'calendar', 'contacts', 'dashboard', 'files', 'tasks'];

	public function __construct(
		private INavigationManager $navigationManager,
	) {
	}

	/**
	 * Apps in the current user's navigation, so restricted apps stay out
	 *
	 * @return list<string>
	 */
	public function get(): array {
		$apps = array_map(
			static fn (array $entry): string => $entry['app'] ?? $entry['id'],
			$this->navigationManager->getAll(INavigationManager::TYPE_APPS),
		);
		return array_values(array_unique(array_diff($apps, self::EXCLUDED)));
	}
}
