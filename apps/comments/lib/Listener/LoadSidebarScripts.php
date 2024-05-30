<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Listener;

use OCA\Comments\AppInfo\Application;
use OCA\Files\Event\LoadSidebar;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/** @template-implements IEventListener<LoadSidebar> */
class LoadSidebarScripts implements IEventListener {
	public function __construct(
		private ICommentsManager $commentsManager,
		private IInitialState $initialState,
		private IAppManager $appManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof LoadSidebar)) {
			return;
		}

		$this->commentsManager->load();

		$this->initialState->provideInitialState('activityEnabled', $this->appManager->isEnabledForUser('activity'));
		// Add comments sidebar tab script
		Util::addScript(Application::APP_ID, 'comments-tab', 'files');
	}
}
