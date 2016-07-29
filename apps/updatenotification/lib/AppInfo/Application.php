<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\UpdateNotification\AppInfo;

use OC\AppFramework\Utility\TimeFactory;
use OC\Updater;
use OCA\UpdateNotification\Controller\AdminController;
use OCA\UpdateNotification\UpdateChecker;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;

class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('updatenotification', $urlParams);
		$container = $this->getContainer();

		$container->registerService('AdminController', function(IAppContainer $c) {
			$updater = new \OC\Updater\VersionCheck(
				\OC::$server->getHTTPClientService(),
				\OC::$server->getConfig()
			);
			return new AdminController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->getServer()->getJobList(),
				$c->getServer()->getSecureRandom(),
				$c->getServer()->getConfig(),
				new TimeFactory(),
				$c->getServer()->getL10N($c->query('AppName')),
				new UpdateChecker($updater),
				$c->getServer()->getDateTimeFormatter()
			);
		});
	}

}
