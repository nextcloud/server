<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Morris Jobke <hey@morrisjobke.de>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\FederatedFileSharing\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when a federated share is successfully added
 *
 * @since 20.0.0
 */
class FederatedShareAddedEvent extends Event {

	/** @var string */
	private $remote;

	/**
	 * @since 20.0.0
	 */
	public function __construct(string $remote) {
		$this->remote = $remote;
	}

	/**
	 * @since 20.0.0
	 */
	public function getRemote(): string {
		return $this->remote;
	}
}
