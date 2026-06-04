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

	/** @var string */
	protected $event;
	/** @var string */
	protected $appID;
	/** @var \OCP\IGroup[]|null */
	protected $groups;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param $appID
	 * @param \OCP\IGroup[]|null $groups
	 * @since 9.0.0
	 */
	public function __construct($event, $appID, ?array $groups = null) {
		$this->event = $event;
		$this->appID = $appID;
		$this->groups = $groups;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getAppID() {
		return $this->appID;
	}

	/**
	 * returns the group Ids
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getGroups() {
		return array_map(function ($group) {
			/** @var \OCP\IGroup $group */
			return $group->getGID();
		}, $this->groups);
	}
}
