<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profile\Listener;

use OCA\Settings\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;

/** @template-implements IEventListener<LoadAdditionalEntriesEvent> */
class LoadAdditionalEntriesListener implements IEventListener {

	public function __construct(
		private readonly IL10N $l10n,
		private readonly IAppManager $appManger,
		private readonly INavigationManager $navigationManager,
		private readonly IURLGenerator $urlGenerator,
		private readonly IUserSession $userSession,
	) {
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

	private function registerNavigationEntries(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$this->navigationManager->add([
			'type' => 'settings',
			'id' => 'profile',
			'order' => 1,
			'href' => $this->urlGenerator->linkToRoute(
				'profile.ProfilePage.index',
				['targetUserId' => $user->getUID()],
			),
			'name' => $this->l10n->t('View profile'),
		]);
	}

}
