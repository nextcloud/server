<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Diagnostics\IEvent;

class Event implements IEvent {
	/**
	 * @var float
	 */
	protected $end;

	/**
	 * @param string $id
	 * @param string $description
	 * @param float $start
	 */
	public function __construct(
		protected $id,
		protected $description,
		protected $start,
	) {
	}

	/**
	 * @param float $time
	 */
	public function end($time) {
		$this->end = $time;
	}

	/**
	 * @return float
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return float
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * @return float
	 */
	public function getDuration() {
		if (!$this->end) {
			$this->end = microtime(true);
		}
		return $this->end - $this->start;
	}

	public function __toString(): string {
		return $this->getId() . ' ' . $this->getDescription() . ' ' . $this->getDuration();
	}
}
