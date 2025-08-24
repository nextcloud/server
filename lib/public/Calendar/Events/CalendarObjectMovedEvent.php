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
class CalendarObjectMovedEvent extends Event implements IWebhookCompatibleEvent {
	/**
	 * @since 32.0.0
	 */
	public function __construct(
		private int $sourceCalendarId,
		private array $sourceCalendarData,
		private int $targetCalendarId,
		private array $targetCalendarData,
		private array $sourceShares,
		private array $targetShares,
		private array $objectData,
	) {
		parent::__construct();
	}

	/**
	 * @return int
	 * @since 32.0.0
	 */
	public function getSourceCalendarId(): int {
		return $this->sourceCalendarId;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getSourceCalendarData(): array {
		return $this->sourceCalendarData;
	}

	/**
	 * @return int
	 * @since 32.0.0
	 */
	public function getTargetCalendarId(): int {
		return $this->targetCalendarId;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getTargetCalendarData(): array {
		return $this->targetCalendarData;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getSourceShares(): array {
		return $this->sourceShares;
	}

	/**
	 * @return array
	 * @since 32.0.0
	 */
	public function getTargetShares(): array {
		return $this->targetShares;
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
			'sourceCalendarId' => $this->getSourceCalendarId(),
			'sourceCalendarData' => $this->getSourceCalendarData(),
			'targetCalendarId' => $this->getTargetCalendarId(),
			'targetCalendarData' => $this->getTargetCalendarData(),
			'sourceShares' => $this->getSourceShares(),
			'targetShares' => $this->getTargetShares(),
			'objectData' => $this->getObjectData(),
		];
	}
}
