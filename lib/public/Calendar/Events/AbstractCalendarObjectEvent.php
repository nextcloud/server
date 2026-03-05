<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Calendar\Events;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

/**
 * @since 32.0.0
 */
abstract class AbstractCalendarObjectEvent extends Event implements IWebhookCompatibleEvent {

	/**
	 * @param int $calendarId
	 * @param array $calendarData
	 * @param array $shares
	 * @param array $objectData
	 * @since 32.0.0
	 */
	public function __construct(
		private int $calendarId,
		private array $calendarData,
		private array $shares,
		private array $objectData,
	) {
		parent::__construct();
	}

	/**
	 * @return int
	 * @since 32.0.0
	 */
	public function getCalendarId(): int {
		return $this->calendarId;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getCalendarData(): array {
		return $this->calendarData;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getObjectData(): array {
		return $this->objectData;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getWebhookSerializable(): array {
		return [
			'calendarId' => $this->getCalendarId(),
			'calendarData' => $this->getCalendarData(),
			'shares' => $this->getShares(),
			'objectData' => $this->getObjectData(),
		];
	}
}
