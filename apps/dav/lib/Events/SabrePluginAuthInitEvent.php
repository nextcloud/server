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
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;
use Sabre\DAV\Server;

/**
 * This event is triggered during the setup of the SabreDAV server to allow the
 * registration of additional authentication backends.
 *
 * @since 20.0.0
 */
class SabrePluginAuthInitEvent extends Event {

	/** @var Server */
	private $server;

	/**
	 * @since 20.0.0
	 */
	public function __construct(Server $server) {
		$this->server = $server;
	}

	/**
	 * @since 20.0.0
	 */
	public function getServer(): Server {
		return $this->server;
	}
}
