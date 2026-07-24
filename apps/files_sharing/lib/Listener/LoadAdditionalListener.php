<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\External\Manager as ExternalManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Util;

/** @template-implements IEventListener<LoadAdditionalScriptsEvent> */
class LoadAdditionalListener implements IEventListener {

	public function __construct(
		private IInitialState $initialState,
		private IConfig $config,
		private IManager $shareManager,
		private IUserSession $userSession,
		private ExternalManager $externalManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		// After files for the breadcrumb share indicator
		Util::addScript(Application::APP_ID, 'additionalScripts', 'files');
		Util::addStyle(Application::APP_ID, 'icons');

		if ($this->shareManager->shareApiEnabled()) {
			Util::addInitScript(Application::APP_ID, 'init');
		}

		$this->provideInitialStates();
	}

	private function provideInitialStates(): void {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return;
		}

		$acceptDefault = $this->config->getSystemValueBool('sharing.enable_share_accept');
		$this->initialState->provideInitialState('sharing.enable_share_accept', $acceptDefault);

		$hasPendingShares = $this->hasPendingShares($user->getUID());
		$this->initialState->provideInitialState('has_pending_shares', $hasPendingShares);
	}

	private function hasPendingShares(string $userId): bool {
		foreach ([IShare::TYPE_USER, IShare::TYPE_GROUP] as $shareType) {
			$shares = $this->shareManager->getSharedWith($userId, $shareType, null, -1, 0);
			foreach ($shares as $share) {
				if ($share->getStatus() === IShare::STATUS_PENDING || $share->getStatus() === IShare::STATUS_REJECTED) {
					return true;
				}
			}
		}

		return !empty($this->externalManager->getOpenShares());
	}
}
