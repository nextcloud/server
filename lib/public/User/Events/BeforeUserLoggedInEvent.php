<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCP\User\Events;

use OCP\Authentication\IApacheBackend;
use OCP\EventDispatcher\Event;

/**
 * @since 18.0.0
 */
class BeforeUserLoggedInEvent extends Event {
	private string $username;
	private ?string $password;
	private ?IApacheBackend $backend;

	/**
	 * @since 18.0.0
	 * @since 26.0.0 password can be null
	 */
	public function __construct(string $username, ?string $password, ?IApacheBackend $backend = null) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;
		$this->backend = $backend;
	}

	/**
	 * returns the login name, which must not necessarily match to a user ID
	 *
	 * @since 18.0.0
	 */
	public function getUsername(): string {
		return $this->username;
	}

	/**
	 * @since 18.0.0
	 * @since 26.0.0 value can be null
	 */
	public function getPassword(): ?string {
		return $this->password;
	}

	/**
	 * return backend if available (or null)
	 *
	 * @return IApacheBackend|null
	 * @since 26.0.0
	 */
	public function getBackend(): ?IApacheBackend {
		return $this->backend;
	}
}
