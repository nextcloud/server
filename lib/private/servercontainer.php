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

namespace OC;


use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Utility\SimpleContainer;
use OCP\AppFramework\QueryException;

/**
 * Class ServerContainer
 *
 * @package OC
 */
class ServerContainer extends SimpleContainer {
	/** @var DIContainer[] */
	protected $appContainers;

	/**
	 * ServerContainer constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->appContainers = [];
	}

	/**
	 * @param string $appName
	 * @param DIContainer $container
	 */
	public function registerAppContainer($appName, DIContainer $container) {
		$this->appContainers[$appName] = $container;
	}

	/**
	 * @param string $appName
	 * @return DIContainer
	 */
	public function getAppContainer($appName) {
		if (isset($this->appContainers[$appName])) {
			return $this->appContainers[$appName];
		}

		return new DIContainer($appName);
	}
}
