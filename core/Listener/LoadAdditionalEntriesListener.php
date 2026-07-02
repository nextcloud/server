<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Listener;

use OCA\Settings\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
class LoadAdditionalEntriesListener implements IEventListener {
	private readonly IL10N $l10n;

	public function __construct(
		public readonly IFactory $l10nFactory,
		private readonly IAppManager $appManger,
		private readonly INavigationManager $navigationManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly IUserSession $userSession,
	) {
		$this->l10n = $this->l10nFactory->get('core');
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalEntriesEvent)) {
			return;
		}

		if (!$this->userSession->isLoggedIn()) {
			return;
		}

		if ($this->appManger->isAppLoaded(Application::APP_ID)) {
			$this->registerNavigationEntries();
		}

	}

	/**
	 * Registers the navigation entries for the core app:
	 * - The logout button in the settings menu
	 */
	private function registerNavigationEntries(): void {
		// Register the logout button in the user settings
		$logoutUrl = \OC_User::getLogoutUrl($this->urlGenerator);
		if ($logoutUrl !== '') {
			$this->navigationManager->add([
				'type' => 'settings',
				'id' => 'logout',
				'order' => 99999,
				'href' => $logoutUrl,
				'name' => $this->l10n->t('Log out'),
				'icon' => $this->urlGenerator->imagePath('core', 'actions/logout.svg'),
			]);
		}
	}

}
