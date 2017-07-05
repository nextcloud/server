<?php
/**
 * @copyright Copyright (c) 2017 Roger Szabo <roger.szabo@web.de>
 *
 * @author Roger Szabo <roger.szabo@web.de>
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

namespace OCA\User_LDAP\AppInfo;

use OCA\User_LDAP\Controller\RenewPasswordController;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;

class Application extends App {
	public function __construct () {
		parent::__construct('user_ldap');
		$container = $this->getContainer();

		/**
		 * Controller
		 */
		$container->registerService('RenewPasswordController', function(IAppContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');

			return new RenewPasswordController(
				$c->getAppName(),
				$server->getRequest(),
				$c->query('UserManager'),
				$server->getConfig(),
				$c->query('OCP\IL10N'),
				$c->query('Session'),
				$server->getURLGenerator()
			);
		});
	}
}
