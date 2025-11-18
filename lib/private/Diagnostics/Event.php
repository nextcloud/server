<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Diagnostics\IEvent;

class Event implements IEvent {
	protected ?float $end = null;

	public function __construct(
		protected string $id,
		protected string $description,
		protected float $start,
	) {
	}

	public function end(float $time): void {
		$this->end = $time;
	}

	public function getStart(): float {
		return $this->start;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getEnd(): float {
		return $this->end ?? -1;
	}

	public function getDuration(): float {
		if (!$this->end) {
			$this->end = microtime(true);
		}
		return $this->end - $this->start;
	}

	public function __toString(): string {
		return $this->getId() . ' ' . $this->getDescription() . ' ' . $this->getDuration();
	}
}
