<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Token;

use OCP\App\IAppManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class GrantableApps {
	/**
	 * Apps whose data is reached through scopes, like DAV for calendars and contacts
	 */
	private const EXCLUDED = ['calendar', 'contacts', 'dav', 'files', 'files_sharing', 'files_trashbin', 'files_versions', 'notifications', 'tasks'];

	public function __construct(
		private IAppManager $appManager,
		private IUserSession $userSession,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getAppIds(): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}
		return array_values(array_diff($this->appManager->getEnabledAppsForUser($user), self::EXCLUDED));
	}

	/**
	 * Sorted by name, for the picker
	 *
	 * @return list<array{id: string, name: string, icon: string}>
	 */
	public function entries(): array {
		$language = $this->l10nFactory->findLanguage();
		$entries = [];
		foreach ($this->getAppIds() as $app) {
			$name = $this->appManager->getAppInfo($app, false, $language)['name'] ?? null;
			$entries[] = ['id' => $app, 'name' => is_string($name) ? $name : $app, 'icon' => $this->appManager->getAppIcon($app) ?? $this->urlGenerator->imagePath('core', 'places/default-app-icon.svg')];
		}
		usort($entries, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));
		return $entries;
	}
}
