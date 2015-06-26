<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Files\Config;

use OC\Hooks\Emitter;
use OC\Hooks\EmitterTrait;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class MountProviderCollection implements IMountProviderCollection, Emitter {
	use EmitterTrait;

	/**
	 * @var \OCP\Files\Config\IMountProvider[]
	 */
	private $providers = array();

	/**
	 * @var \OCP\Files\Storage\IStorageFactory
	 */
	private $loader;

	/**
	 * @param \OCP\Files\Storage\IStorageFactory $loader
	 */
	public function __construct(IStorageFactory $loader) {
		$this->loader = $loader;
	}

	/**
	 * Get all configured mount points for the user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user) {
		$loader = $this->loader;
		return array_reduce($this->providers, function ($mounts, IMountProvider $provider) use ($user, $loader) {
			return array_merge($mounts, $provider->getMountsForUser($user, $loader));
		}, array());
	}

	/**
	 * Add a provider for mount points
	 *
	 * @param \OCP\Files\Config\IMountProvider $provider
	 */
	public function registerProvider(IMountProvider $provider) {
		$this->providers[] = $provider;
		$this->emit('\OC\Files\Config', 'registerMountProvider', [$provider]);
	}
}
