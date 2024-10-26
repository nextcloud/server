<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Listeners;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/** @template-implements IEventListener<LoadAdditionalScriptsEvent> */
class LoadAdditionalScriptsListener implements IEventListener {
	public function __construct(
		private FederatedShareProvider $federatedShareProvider,
		private IInitialState $initialState,
		private IAppManager $appManager,
	) {
		$this->federatedShareProvider = $federatedShareProvider;
		$this->initialState = $initialState;
		$this->appManager = $appManager;
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled()) {
			$this->initialState->provideInitialState('notificationsEnabled', $this->appManager->isEnabledForUser('notifications'));
			Util::addInitScript('federatedfilesharing', 'external');
		}
	}
}
