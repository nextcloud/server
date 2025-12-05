<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OC\Share20\DefaultShareProvider;
use OCA\Files_Sharing\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\IConfig;
use OCP\Share\Events\UserAddedToShareEvent;
use OCP\Share\IManager;

/** @template-implements IEventListener<UserAddedEvent> */
class UserAddedToGroupListener implements IEventListener {

	public function __construct(
		private readonly IManager $shareManager,
		private readonly IConfig $config,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly DefaultShareProvider $shareProvider,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof UserAddedEvent)) {
			return;
		}

		$user = $event->getUser();
		$group = $event->getGroup();

		$shares = $this->shareProvider->getSharedWithGroup($group->getGID());

		foreach ($shares as $share) {
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
