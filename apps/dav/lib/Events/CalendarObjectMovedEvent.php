<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
