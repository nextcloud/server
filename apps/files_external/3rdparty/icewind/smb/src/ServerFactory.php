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

	/** @var System|null */
	private $system = null;

	/**
	 * @param $host
	 * @param IAuth $credentials
	 * @return IServer
	 * @throws DependencyException
	 */
	public function createServer($host, IAuth $credentials) {
		foreach (self::BACKENDS as $backend) {
			if (call_user_func("$backend::available", $this->getSystem())) {
				return new $backend($host, $credentials, $this->getSystem(), new TimeZoneProvider($host, $this->getSystem()));
			}
		}

		throw new DependencyException('No valid backend available, ensure smbclient is in the path or php-smbclient is installed');
	}

	/**
	 * @return System
	 */
	private function getSystem() {
		if (is_null($this->system)) {
			$this->system = new System();
		}

		return $this->system;
	}
}
