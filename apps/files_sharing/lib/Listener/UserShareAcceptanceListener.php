<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-implements IEventListener<ShareCreatedEvent> */
class UserShareAcceptanceListener implements IEventListener {

	public function __construct(
		private IConfig $config,
		private IManager $shareManager,
		private IGroupManager $groupManager,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ShareCreatedEvent)) {
			return;
		}

		$share = $event->getShare();

		if ($share->getShareType() === IShare::TYPE_USER) {
			$this->handleAutoAccept($share, $share->getSharedWith());
		} elseif ($share->getShareType() === IShare::TYPE_GROUP) {
			$group = $this->groupManager->get($share->getSharedWith());

			if ($group === null) {
				return;
			}

			$users = $group->getUsers();
			foreach ($users as $user) {
				$this->handleAutoAccept($share, $user->getUID());
			}
		}
	}

	private function handleAutoAccept(IShare $share, string $userId) {
		$defaultAcceptSystemConfig = $this->config->getSystemValueBool('sharing.enable_share_accept', false) ? 'no' : 'yes';
		$acceptDefault = $this->config->getUserValue($userId, Application::APP_ID, 'default_accept', $defaultAcceptSystemConfig) === 'yes';
		if (!$this->config->getSystemValueBool('sharing.force_share_accept', false) && $acceptDefault) {
			$this->shareManager->acceptShare($share, $userId);
		}
	}
}
