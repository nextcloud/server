<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Diagnostics\IQuery;

class Query implements IQuery {
	private ?float $end = null;

	public function __construct(
		private string $sql,
		private array $params,
		private float $start,
		private array $stack,
	) {
	}

	public function end($time): void {
		$this->end = $time;
	}

	public function getParams(): array {
		return $this->params;
	}

	public function getSql(): string {
		return $this->sql;
	}

	public function getStart(): float {
		return $this->start;
	}

	public function getDuration(): float {
		return $this->end - $this->start;
	}

	public function getStartTime(): float {
		return $this->start;
	}

	public function getStacktrace(): array {
		return $this->stack;
	}
}
