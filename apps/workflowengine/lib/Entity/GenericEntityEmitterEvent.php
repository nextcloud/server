<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Entity;

class GenericEntityEmitterEvent implements IEntityEmitterEvent {
	/** @var string */
	private $emitterClassName;
	/** @var string */
	private $eventName;
	/** @var string */
	private $displayName;
	/** @var string */
	private $slot;

	public function __construct(string $emitterClassName, string $slot, string $eventName, string $displayName) {
		$this->emitterClassName = $emitterClassName;
		$this->eventName = $eventName;
		$this->displayName = $displayName;
		$this->slot = $slot;
	}

	public function getEmitterClassName(): string {
		return $this->emitterClassName;
	}

	public function getSlot(): string {
		return $this->slot;
	}

	public function getDisplayName(): string {
		return $this->displayName;
	}

	public function getEventName(): string {
		return $this->eventName;
	}
}
