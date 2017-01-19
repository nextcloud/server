<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace OC\Core;

use OC\Core\Controller\OCJSController;
use OC\Security\IdentityProof\Manager;
use OC\Server;
use OCP\AppFramework\App;
use OCP\Util;

/**
 * Class Application
 *
 * @package OC\Core
 */
class Application extends App {

	public function __construct() {
		parent::__construct('core');

		$container = $this->getContainer();

		$container->registerService('defaultMailAddress', function () {
			return Util::getDefaultEmailAddress('lostpassword-noreply');
		});
		$container->registerService(Manager::class, function () {
			return new Manager(
				\OC::$server->getAppDataDir('identityproof'),
				\OC::$server->getCrypto()
			);
		});

		$container->registerService(OCJSController::class, function () use ($container) {
			/** @var Server $server */
			$server = $container->getServer();
			return new OCJSController(
				$container->query('appName'),
				$server->getRequest(),
				$server->getL10N('core'),
				// This is required for the theming to overwrite the `OC_Defaults`, see
				// https://github.com/nextcloud/server/issues/3148
				$server->getThemingDefaults(),
				$server->getAppManager(),
				$server->getSession(),
				$server->getUserSession(),
				$server->getConfig(),
				$server->getGroupManager(),
				$server->getIniWrapper(),
				$server->getURLGenerator()
			);
		});
	}
}
