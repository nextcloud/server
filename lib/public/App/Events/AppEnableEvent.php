<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\App\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when an app is enabled.
 *
 * @since 27.0.0
 */
class AppEnableEvent extends Event {
	private string $appId;
	/** @var string[] */
	private array $groupIds;

	/**
	 * @param string[] $groupIds
	 * @since 27.0.0
	 */
	public function __construct(string $appId, array $groupIds = []) {
		parent::__construct();

		$this->appId = $appId;
		$this->groupIds = $groupIds;
	}

	/**
	 * @since 27.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}

	/**
	 * @since 27.0.0
	 */
	public function getGroupIds(): array {
		return $this->groupIds;
	}
}
