<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OCA\Files;

use OC\NavigationManager;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;

class App {
	private static ?INavigationManager $navigationManager = null;

	/**
	 * Returns the app's navigation manager
	 */
	public static function getNavigationManager(): INavigationManager {
		// TODO: move this into a service in the Application class
		if (self::$navigationManager === null) {
			self::$navigationManager = new NavigationManager(
				Server::get(IAppManager::class),
				Server::get(IUrlGenerator::class),
				Server::get(IFactory::class),
				Server::get(IUserSession::class),
				Server::get(IGroupManager::class),
				Server::get(IConfig::class)
			);
			self::$navigationManager->clear(false);
		}
		return self::$navigationManager;
	}

	public static function extendJsConfig($settings): void {
		$appConfig = json_decode($settings['array']['oc_appconfig'], true);

		$maxChunkSize = (int)Server::get(IConfig::class)->getAppValue('files', 'max_chunk_size', (string)(10 * 1024 * 1024));
		$appConfig['files'] = [
			'max_chunk_size' => $maxChunkSize
		];

		$settings['array']['oc_appconfig'] = json_encode($appConfig);
	}
}
