<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\User\IOutOfOfficeData;

/**
 * Emitted when a user's out-of-office period started
 *
 * @since 28.0.0
 */
class OutOfOfficeStartedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(private IOutOfOfficeData $data) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getData(): IOutOfOfficeData {
		return $this->data;
	}
}
