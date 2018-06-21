<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace Icewind\SMB;


abstract class AbstractServer implements IServer {
	const LOCALE = 'en_US.UTF-8';

	/**
	 * @var string $host
	 */
	protected $host;

	/**
	 * @var IAuth $user
	 */
	protected $auth;

	/**
	 * @var \Icewind\SMB\System
	 */
	protected $system;

	/**
	 * @var TimeZoneProvider
	 */
	protected $timezoneProvider;

	/**
	 * @param string $host
	 * @param IAuth $auth
	 * @param System $system
	 * @param TimeZoneProvider $timeZoneProvider
	 */
	public function __construct($host, IAuth $auth, System $system, TimeZoneProvider $timeZoneProvider) {
		$this->host = $host;
		$this->auth = $auth;
		$this->system = $system;
		$this->timezoneProvider = $timeZoneProvider;
	}

	/**
	 * @return IAuth
	 */
	public function getAuth() {
		return $this->auth;
	}

	/**
	 * return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getTimeZone() {
		return $this->timezoneProvider->get();
	}

	public function getSystem() {
		return $this->system;
	}
}
