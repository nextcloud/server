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

use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Native\NativeServer;
use Icewind\SMB\Wrapped\Server;

class ServerFactory {
	const BACKENDS = [
		NativeServer::class,
		Server::class
	];

	/** @var System */
	private $system;

	/** @var IOptions */
	private $options;

	/** @var ITimeZoneProvider */
	private $timeZoneProvider;

	/**
	 * ServerFactory constructor.
	 *
	 * @param IOptions|null $options
	 * @param ISystem|null $system
	 * @param ITimeZoneProvider|null $timeZoneProvider
	 */
	public function __construct(
		IOptions $options = null,
		ISystem $system = null,
		ITimeZoneProvider $timeZoneProvider = null
	) {
		if (is_null($options)) {
			$options = new Options();
		}
		if (is_null($system)) {
			$system = new System();
		}
		if (is_null($timeZoneProvider)) {
			$timeZoneProvider = new TimeZoneProvider($system);
		}
		$this->options = $options;
		$this->system = $system;
		$this->timeZoneProvider = $timeZoneProvider;
	}


	/**
	 * @param $host
	 * @param IAuth $credentials
	 * @return IServer
	 * @throws DependencyException
	 */
	public function createServer($host, IAuth $credentials) {
		foreach (self::BACKENDS as $backend) {
			if (call_user_func("$backend::available", $this->system)) {
				return new $backend($host, $credentials, $this->system, $this->timeZoneProvider, $this->options);
			}
		}

		throw new DependencyException('No valid backend available, ensure smbclient is in the path or php-smbclient is installed');
	}
}
