<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Diagnostics;

use OCP\Diagnostics\IQuery;

class Query implements IQuery {
	private float $end = 0;

	public function __construct(
		private readonly string $sql,
		private readonly ?array $params,
		private readonly ?array $types,
		private readonly float $start,
		private readonly array $stack,
	) {
	}

	public function end(float $time): void {
		$this->end = $time;
	}

	#[\Override]
	public function getParams(): ?array {
		return $this->params;
	}

	#[\Override]
	public function getTypes(): ?array {
		return $this->types;
	}

	#[\Override]
	public function getSql(): string {
		return $this->sql;
	}

	#[\Override]
	public function getStart(): float {
		return $this->start;
	}

	#[\Override]
	public function getDuration(): float {
		return $this->end - $this->start;
	}

	#[\Override]
	public function getStartTime(): float {
		return $this->start;
	}

	#[\Override]
	public function getStacktrace(): array {
		return $this->stack;
	}
}
