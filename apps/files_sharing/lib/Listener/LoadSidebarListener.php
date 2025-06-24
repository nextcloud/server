<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files\Event\LoadSidebar;
use OCA\Files_Sharing\AppInfo\Application;
use OCA\Files_Sharing\Config\ConfigLexicon;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Util;

/**
 * @template-implements IEventListener<LoadSidebar>
 */
class LoadSidebarListener implements IEventListener {

	public function __construct(
		private IInitialState $initialState,
		private IManager $shareManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadSidebar)) {
			return;
		}

		$appConfig = Server::get(IAppConfig::class);
		$this->initialState->provideInitialState('showFederatedSharesAsInternal', $appConfig->getValueBool('files_sharing', ConfigLexicon::SHOW_FEDERATED_AS_INTERNAL));
		$this->initialState->provideInitialState('showFederatedSharesToTrustedServersAsInternal', $appConfig->getValueBool('files_sharing', ConfigLexicon::SHOW_FEDERATED_TO_TRUSTED_AS_INTERNAL));
		Util::addScript(Application::APP_ID, 'files_sharing_tab', 'files');
	}
}
