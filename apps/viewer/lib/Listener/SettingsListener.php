<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Viewer\Listener;

use OCA\Viewer\Service\UserConfig;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;

/**
 * Hand the viewer the user's settings, on every page core puts the viewer on.
 *
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
class SettingsListener implements IEventListener {
	public function __construct(
		private IInitialState $initialState,
		private IUserSession $userSession,
		private UserConfig $userConfig,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)
			|| $event->getResponse()->getRenderAs() === TemplateResponse::RENDER_AS_ERROR) {
			return;
		}

		// A guest has nowhere to keep them, and no settings tells the viewer
		// not to save any
		if ($this->userSession->getUser() === null) {
			return;
		}
		$this->initialState->provideInitialState('config', $this->userConfig->getConfigs());
	}
}
