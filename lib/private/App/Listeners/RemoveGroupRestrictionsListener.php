<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\App\Listeners;

use OCP\App\IAppManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

/**
 * @template-implements IEventListener<GroupDeletedEvent>
 */
class RemoveGroupRestrictionsListener implements IEventListener {
	public function __construct(
		private readonly IAppManager $appManager,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof GroupDeletedEvent) {
			return;
		}

		$group = $event->getGroup();
		$apps = $this->appManager->getEnabledAppsForGroup($group);
		foreach ($apps as $appId) {
			$restrictions = $this->appManager->getAppRestriction($appId);
			if (empty($restrictions)) {
				continue;
			}
			$key = array_search($group->getGID(), $restrictions, true);
			unset($restrictions[$key]);
			$restrictions = array_values($restrictions);
			if (empty($restrictions)) {
				$this->appManager->disableApp($appId);
			} else {
				$this->appManager->enableAppForGroups($appId, $restrictions);
			}
		}
	}
}
