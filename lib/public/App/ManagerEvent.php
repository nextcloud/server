<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\App;

use OCP\EventDispatcher\Event;

/**
 * Class ManagerEvent
 *
 * @since 9.0.0
 * @deprecated 22.0.0 Use AppEnabledEvent, AppDisableEvent and AppUpdateEvent instead
 */
class ManagerEvent extends Event {
	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_APP_ENABLE = 'OCP\App\IAppManager::enableApp';

	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_APP_ENABLE_FOR_GROUPS = 'OCP\App\IAppManager::enableAppForGroups';

	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_APP_DISABLE = 'OCP\App\IAppManager::disableApp';

	/**
	 * @since 9.1.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_APP_UPDATE = 'OCP\App\IAppManager::updateApp';

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param $appID
	 * @param list<\OCP\IGroup|string>|null $groups
	 * @since 9.0.0
	 */
	public function __construct(
		private readonly string $event,
		private readonly string $appID,
		private readonly ?array $groups = null,
	) {
	}

	/**
	 * @since 9.0.0
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getAppID(): string {
		return $this->appID;
	}

	/**
	 * returns the group Ids
	 * @return list<string>
	 * @since 9.0.0
	 */
	public function getGroups(): array {
		return array_map(function (\OCP\IGroup|string $group): string {
			return ($group instanceof \OCP\IGroup)
				? $group->getGID()
				: $group;
		}, $this->groups);
	}
}
