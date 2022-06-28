<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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
namespace OCP\Federation\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 25.0.0
 */
class TrustedServerRemovedEvent extends Event {
	private string $urlHash;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $urlHash) {
		parent::__construct();
		$this->urlHash = $urlHash;
	}

	/**
	 * @since 25.0.0
	 */
	public function getUrlHash(): string {
		return $this->urlHash;
	}
}
