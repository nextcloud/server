<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\Files_External\AppInfo\Application;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Util;

/**
 * @template-implements IEventListener<LoadAdditionalScriptsEvent>
 */
class LoadAdditionalListener implements IEventListener {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		$allowUserMounting = $this->config->getAppValue('files_external', 'allow_user_mounting', 'no') === 'yes';
		$this->initialState->provideInitialState('allowUserMounting', $allowUserMounting);

		Util::addInitScript(Application::APP_ID, 'init');
	}
}
