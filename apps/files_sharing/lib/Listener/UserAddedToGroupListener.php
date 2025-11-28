<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IConfig;
use OCP\Share\Events\UserAddedToShareEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<UserAddedEvent> */
class UserAddedToGroupListener implements IEventListener {

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IConfig $config,
		private readonly IEventDispatcher $eventDispatcher,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		// todo: add a way to get shares by group id
		// Get all group shares this user has access to now to filter later
		$shares = $this->shareManager->getSharedWith($user->getUID(), IShare::TYPE_GROUP, null, -1);

		foreach ($shares as $share) {
			// If this is not the new group we can skip it
			if ($share->getSharedWith() !== $group->getGID()) {
				continue;
			}

			// Accept the share if needed
			if ($this->hasAutoAccept($user->getUID())) {
				$this->shareManager->acceptShare($share, $user->getUID());
			}

			$this->eventDispatcher->dispatchTyped(new UserAddedToShareEvent($share, $user));
		}
	}


	private function hasAutoAccept(string $userId): bool {
		$defaultAcceptSystemConfig = $this->config->getSystemValueBool('sharing.enable_share_accept', false) ? 'no' : 'yes';
		$acceptDefault = $this->config->getUserValue($userId, Application::APP_ID, 'default_accept', $defaultAcceptSystemConfig) === 'yes';
		return (!$this->config->getSystemValueBool('sharing.force_share_accept', false) && $acceptDefault);
	}
}
