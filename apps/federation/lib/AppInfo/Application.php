<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Federation\AppInfo;

use OCA\Federation\DAV\FedAuth;
use OCA\Federation\Middleware\AddServerMiddleware;
use OCP\AppFramework\App;
use OCP\SabrePluginEvent;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Server;

class Application extends App {

	/**
	 * @param array $urlParams
	 */
	public function __construct($urlParams = []) {
		parent::__construct('federation', $urlParams);
		$this->registerMiddleware();
	}

	private function registerMiddleware() {
		$container = $this->getContainer();
		$container->registerAlias('AddServerMiddleware', AddServerMiddleware::class);
		$container->registerMiddleWare('AddServerMiddleware');
	}

	public function registerHooks() {
		$container = $this->getContainer();

		$dispatcher = $container->getServer()->getEventDispatcher();
		$dispatcher->addListener('OCA\DAV\Connector\Sabre::authInit', function ($event) use ($container) {
			if ($event instanceof SabrePluginEvent) {
				$server = $event->getServer();
				if ($server instanceof Server) {
					$authPlugin = $server->getPlugin('auth');
					if ($authPlugin instanceof Plugin) {
						$authPlugin->addBackend($container->query(FedAuth::class));
					}
				}
			}
		});
	}
}
