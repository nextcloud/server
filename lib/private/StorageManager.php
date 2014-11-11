<?php
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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
namespace OC;

use OC\Cache\CappedMemoryCache;
use OC\Files\Node\HookConnector;
use OC\Files\Node\Root;
use OC\Files\View;
use OCP\IStorageManager;
use OCP\IUserManager;
use OCP\IUserSession;

class StorageManager implements IStorageManager {

	/**
	 * @var IUserManager $userManager
	 */
	protected $userManager;

	/**
	 * @var IUserSession $userSession
	 */
	protected $userSession;

	/**
	 * @var array $userFolderCache
	 */
	private $userFolderCache;

	/**
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 */
	public function __construct(IUserManager $userManager, IUserSession $userSession) {
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->userFolderCache = new CappedMemoryCache(32);
	}

	/**
	 * Returns the root folder of ownCloud's data directory
	 *
	 * @return \OCP\Files\IRootFolder
	 */
	public function getRootFolder() {
		$manager = \OC\Files\Filesystem::getMountManager(null);
		$view = new View();
		$root = new Root($manager, $view, null);
		$connector = new HookConnector($root, $view);
		$connector->viewToNode();
		return $root;
	}

	/**
	 * Returns a view to ownCloud's files folder
	 *
	 * @param string $userId user ID
	 * @return \OCP\Files\Folder|null
	 */
	public function getUserFolder($userId = null) {
		if($userId === null) {
			$user = $this->userSession->getUser();
			if (!$user) {
				return null;
			}
			$userId = $user->getUID();
		}

		if (!$this->userFolderCache->hasKey($userId)) {
			$root = $this->getRootFolder();
			$this->userFolderCache->set($userId, $root->getUserFolder($userId));
		}

		return $this->userFolderCache->get($userId);
	}

	/**
	 * Returns an app-specific view in ownClouds data directory
	 *
	 * @return \OCP\Files\Folder
	 */
	public function getAppFolder() {
		$dir = '/' . \OC_App::getCurrentApp();
		$root = $this->getRootFolder();
		if (!$root->nodeExists($dir)) {
			return $root->newFolder($dir);
		} else {
			return $root->get($dir);
		}
	}
}
