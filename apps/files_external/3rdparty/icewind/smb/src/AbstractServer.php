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
	 * @var ISystem
	 */
	protected $system;

	/**
	 * @var TimeZoneProvider
	 */
	protected $timezoneProvider;

	/** @var IOptions */
	protected $options;

	/**
	 * @param string $host
	 * @param IAuth $auth
	 * @param ISystem $system
	 * @param TimeZoneProvider $timeZoneProvider
	 * @param IOptions $options
	 */
	public function __construct($host, IAuth $auth, ISystem $system, TimeZoneProvider $timeZoneProvider, IOptions $options) {
		$this->host = $host;
		$this->auth = $auth;
		$this->system = $system;
		$this->timezoneProvider = $timeZoneProvider;
		$this->options = $options;
	}

	public function getAuth() {
		return $this->auth;
	}

	public function getHost() {
		return $this->host;
	}

	public function getTimeZone() {
		return $this->timezoneProvider->get($this->host);
	}

	public function getSystem() {
		return $this->system;
	}

	public function getOptions() {
		return $this->options;
	}
}
