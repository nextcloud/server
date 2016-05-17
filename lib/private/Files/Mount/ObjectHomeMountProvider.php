<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Files\Mount;

use OCP\Files\Config\IHomeMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

/**
 * Mount provider for object store home storages
 */
class ObjectHomeMountProvider implements IHomeMountProvider {
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * ObjectStoreHomeMountProvider constructor.
	 *
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Get the cache mount for a user
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getHomeMountForUser(IUser $user, IStorageFactory $loader) {
		$config = $this->config->getSystemValue('objectstore');
		if (!is_array($config)) {
			return null; //fall back to local home provider
		}

		// sanity checks
		if (empty($config['class'])) {
			\OCP\Util::writeLog('files', 'No class given for objectstore', \OCP\Util::ERROR);
		}
		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}
		$config['arguments']['user'] = $user;
		// instantiate object store implementation
		$config['arguments']['objectstore'] = new $config['class']($config['arguments']);

		return new MountPoint('\OC\Files\ObjectStore\HomeObjectStoreStorage', '/' . $user->getUID(), $config['arguments'], $loader);
	}
}
