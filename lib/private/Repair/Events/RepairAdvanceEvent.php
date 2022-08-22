<?php
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
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairAdvanceEvent extends Event {
	// TODO Is that current step or step increment?
	private int $current;
	private string $description;

	public function __construct(
		int $current,
		string $description
	) {
		$this->current = $current;
		$this->description = $description;
	}

	public function getCurrentStep(): int {
		return $this->current;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
