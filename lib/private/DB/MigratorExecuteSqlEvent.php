<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use OCP\EventDispatcher\Event;

class MigratorExecuteSqlEvent extends Event {
	public function __construct(
		private string $sql,
		private int $current,
		private int $max,
	) {
	}

	public function getSql(): string {
		return $this->sql;
	}

	public function getCurrentStep(): int {
		return $this->current;
	}

	public function getMaxStep(): int {
		return $this->max;
	}
}
