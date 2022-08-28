<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\DB;

use OCP\EventDispatcher\Event;

class MigratorExecuteSqlEvent extends Event {
	private string $sql;
	private int $current;
	private int $max;

	public function __construct(
		string $sql,
		int $current,
		int $max
	) {
		$this->sql = $sql;
		$this->current = $current;
		$this->max = $max;
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
