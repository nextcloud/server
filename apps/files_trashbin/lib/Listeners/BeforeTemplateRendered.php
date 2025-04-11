<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Listeners;

use OCA\Files_Sharing\Event\BeforeTemplateRenderedEvent;
use OCA\Files_Trashbin\Service\ConfigService;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<BeforeTemplateRenderedEvent> */
class BeforeTemplateRendered implements IEventListener {
	public function __construct(
		private IInitialState $initialState,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeTemplateRenderedEvent)) {
			return;
		}

		ConfigService::injectInitialState($this->initialState);
	}
}
