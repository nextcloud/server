<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\DAV\AppInfo;

use OCP\App\IAppManager;
use OC\ServerContainer;
use OCP\AppFramework\QueryException;

/**
 * Manager for DAV plugins from apps, used to register them
 * to the Sabre server.
 */
class PluginManager {

	/**
	 * @var ServerContainer
	 */
	private $container;

	/**
	 * @var IAppManager
	 */
	private $appManager;

	/**
	 * App plugins
	 *
	 * @var array
	 */
	private $plugins = null;

	/**
	 * App collections
	 *
	 * @var array
	 */
	private $collections = null;

	/**
	 * Contstruct a PluginManager
	 *
	 * @param ServerContainer $container server container for resolving plugin classes
	 * @param IAppManager $appManager app manager to loading apps and their info
	 */
	public function __construct(ServerContainer $container, IAppManager $appManager) {
		$this->container = $container;
		$this->appManager = $appManager;
	}

	/**
	 * Returns an array of app-registered plugins
	 *
	 * @return array
	 */
	public function getAppPlugins() {
		if (null === $this->plugins) {
			$this->populate();
		}
		return $this->plugins;
	}

	/**
	 * Returns an array of app-registered collections
	 *
	 * @return array
	 */
	public function getAppCollections() {
		if (null === $this->collections) {
			$this->populate();
		}
		return $this->collections;
	}

	/**
	 * Retrieve plugin and collection list and populate attributes
	 */
	private function populate() {
		$this->plugins = [];
		$this->collections = [];
		foreach ($this->appManager->getInstalledApps() as $app) {
			// load plugins and collections from info.xml
			$info = $this->appManager->getAppInfo($app);
			if (!isset($info['types']) || !in_array('dav', $info['types'], true)) {
				continue;
			}
			$this->loadSabrePluginsFromInfoXml($this->extractPluginList($info));
			$this->loadSabreCollectionsFromInfoXml($this->extractCollectionList($info));
		}
	}

	private function extractPluginList(array $array) {
		if (isset($array['sabre']) && is_array($array['sabre'])) {
			if (isset($array['sabre']['plugins']) && is_array($array['sabre']['plugins'])) {
				if (isset($array['sabre']['plugins']['plugin'])) {
					$items = $array['sabre']['plugins']['plugin'];
					if (!is_array($items)) {
						$items = [$items];
					}
					return $items;
				}
			}
		}
		return [];
	}

	private function extractCollectionList(array $array) {
		if (isset($array['sabre']) && is_array($array['sabre'])) {
			if (isset($array['sabre']['collections']) && is_array($array['sabre']['collections'])) {
				if (isset($array['sabre']['collections']['collection'])) {
					$items = $array['sabre']['collections']['collection'];
					if (!is_array($items)) {
						$items = [$items];
					}
					return $items;
				}
			}
		}
		return [];
	}

	private function loadSabrePluginsFromInfoXml(array $plugins) {
		foreach ($plugins as $plugin) {
			try {
				$this->plugins[] = $this->container->query($plugin);
			} catch (QueryException $e) {
				if (class_exists($plugin)) {
					$this->plugins[] = new $plugin();
				} else {
					throw new \Exception("Sabre plugin class '$plugin' is unknown and could not be loaded");
				}
			}
		}
	}

	private function loadSabreCollectionsFromInfoXml(array $collections) {
		foreach ($collections as $collection) {
			try {
				$this->collections[] = $this->container->query($collection);
			} catch (QueryException $e) {
				if (class_exists($collection)) {
					$this->collections[] = new $collection();
				} else {
					throw new \Exception("Sabre collection class '$collection' is unknown and could not be loaded");
				}
			}
		}
	}

}
