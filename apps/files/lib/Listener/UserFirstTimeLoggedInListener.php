<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Listener;

use OC\Files\Template\TemplateManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\ISetupManager;
use OCP\Files\NotPermittedException;
use OCP\User\Events\UserFirstTimeLoggedInEvent;

/**
 * @template-implements IEventListener<UserFirstTimeLoggedInEvent>
 */
class UserFirstTimeLoggedInListener implements IEventListener {
	public function __construct(
		private readonly TemplateManager $templateManager,
		private readonly ISetupManager $setupManager,
	) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof UserFirstTimeLoggedInEvent) {
			return;
		}

		$user = $event->getUser();
		$this->setupManager->setupForUser($user);

		try {
			// copy skeleton
			$this->templateManager->copySkeleton($user->getUID());
		} catch (NotPermittedException) {
			// read only uses
		}
	}
}
