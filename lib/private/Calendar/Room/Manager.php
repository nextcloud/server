<?php
/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace OC\Calendar\Room;

use OCP\Calendar\Room\IBackend;

class Manager implements \OCP\Calendar\Room\IManager {

	/** @var IBackend[] holds all registered resource backends */
	private $backends = [];

	/**
	 * Registers a resource backend
	 *
	 * @param IBackend $backend
	 * @return void
	 * @since 14.0.0
	 */
	public function registerBackend(IBackend $backend) {
		$this->backends[$backend->getBackendIdentifier()] = $backend;
	}

	/**
	 * Unregisters a resource backend
	 *
	 * @param IBackend $backend
	 * @return void
	 * @since 14.0.0
	 */
	public function unregisterBackend(IBackend $backend) {
		unset($this->backends[$backend->getBackendIdentifier()]);
	}

	/**
	 * @return IBackend[]
	 * @since 14.0.0
	 */
	public function getBackends():array {
		return array_values($this->backends);
	}

	/**
	 * @param string $backendId
	 * @return IBackend|null
	 */
	public function getBackend($backendId):IBackend {
		if (!isset($this->backends[$backendId])) {
			return null;
		}

		return $this->backends[$backendId];
	}

	/**
	 * removes all registered backend instances
	 * @return void
	 * @since 14.0.0
	 */
	public function clear() {
		$this->backends = [];
	}
}
