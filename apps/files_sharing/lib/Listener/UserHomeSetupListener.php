<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCA\Files_Sharing\ShareRecipientUpdater;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\UserHomeSetupEvent;

/**
 * Listen to the users filesystem setup being started, to perform any receiving share
 * work that was postponed.
 *
 * @template-implements IEventListener<UserHomeSetupEvent>
 */
class UserHomeSetupListener implements IEventListener {
	private bool $disabled = false;
	public function __construct(
		private readonly ShareRecipientUpdater $updater,
		private readonly IUserConfig $userConfig,
	) {
	}

	public function setDisabled(bool $disabled): bool {
		$previous = $this->disabled;
		$this->disabled = $disabled;
		return $previous;
	}
	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof UserHomeSetupEvent) {
			return;
		}
		if ($this->disabled) {
			return;
		}

		$user = $event->getUser();
		if ($this->userConfig->getValueBool($user->getUID(), Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, true)) {
			$this->updater->updateForUser($user);
			$this->userConfig->setValueBool($user->getUID(), Application::APP_ID, ConfigLexicon::USER_NEEDS_SHARE_REFRESH, false);
		}
	}

}
