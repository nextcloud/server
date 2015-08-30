<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Connector\Sabre;

use OCP\AppFramework\Http;
use OCP\SabrePluginEvent;
use OCP\SabrePluginException;
use Sabre\DAV\ServerPlugin;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ListenerPlugin extends ServerPlugin {
	/** @var EventDispatcherInterface */
	protected $dispatcher;

	/**
	 * @param EventDispatcherInterface $dispatcher
	 */
	public function __construct(EventDispatcherInterface $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * This initialize the plugin
	 *
	 * @param \Sabre\DAV\Server $server
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('beforeMethod', array($this, 'emitListener'), 15);
	}

	/**
	 * This method is called before any HTTP method and returns http status code 503
	 * in case the system is in maintenance mode.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function emitListener() {
		$event = new SabrePluginEvent();

		$this->dispatcher->dispatch('OC\Connector\Sabre::beforeMethod', $event);

		if ($event->getStatusCode() !== Http::STATUS_OK) {
			throw new SabrePluginException($event->getMessage(), $event->getStatusCode());
		}

		return true;
	}
}
