<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class CalendarObjectMovedEvent
 *
 * @package OCA\DAV\Events
 * @since 25.0.0
 */
class CalendarObjectMovedEvent extends Event {
	private int $sourceCalendarId;
	private array $sourceCalendarData;
	private int $targetCalendarId;
	private array $targetCalendarData;
	private array $sourceShares;
	private array $targetShares;
	private array $objectData;

	/**
	 * @since 25.0.0
	 */
	public function __construct(int $sourceCalendarId,
		array $sourceCalendarData,
		int $targetCalendarId,
		array $targetCalendarData,
		array $sourceShares,
		array $targetShares,
		array $objectData) {
		parent::__construct();
		$this->sourceCalendarId = $sourceCalendarId;
		$this->sourceCalendarData = $sourceCalendarData;
		$this->targetCalendarId = $targetCalendarId;
		$this->targetCalendarData = $targetCalendarData;
		$this->sourceShares = $sourceShares;
		$this->targetShares = $targetShares;
		$this->objectData = $objectData;
	}

	/**
	 * @return int
	 * @since 25.0.0
	 */
	public function getSourceCalendarId(): int {
		return $this->sourceCalendarId;
	}

	/**
	 * @return array
	 * @since 25.0.0
	 */
	public function getSourceCalendarData(): array {
		return $this->sourceCalendarData;
	}

	/**
	 * @return int
	 * @since 25.0.0
	 */
	public function getTargetCalendarId(): int {
		return $this->targetCalendarId;
	}

	/**
	 * @return array
	 * @since 25.0.0
	 */
	public function getTargetCalendarData(): array {
		return $this->targetCalendarData;
	}

	/**
	 * @return array
	 * @since 25.0.0
	 */
	public function getSourceShares(): array {
		return $this->sourceShares;
	}

	/**
	 * @return array
	 * @since 25.0.0
	 */
	public function getTargetShares(): array {
		return $this->targetShares;
	}

	/**
	 * @return array
	 * @since 25.0.0
	 */
	public function getObjectData(): array {
		return $this->objectData;
	}
}
