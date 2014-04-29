<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Mount\Mount;

class Manager {
	const STORAGE = '\OCA\Files_Sharing\External\Storage';

	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	/**
	 * @var \OC\Files\Mount\Manager
	 */
	private $mountManager;

	/**
	 * @var \OC\Files\Storage\Loader
	 */
	private $storageLoader;

	/**
	 * @var \OC\User\Session
	 */
	private $userSession;

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OC\Files\Mount\Manager $mountManager
	 * @param \OC\User\Session $userSession
	 * @param \OC\Files\Storage\Loader $storageLoader
	 */
	public function __construct(\OCP\IDBConnection $connection, \OC\Files\Mount\Manager $mountManager,
								\OC\Files\Storage\Loader $storageLoader, \OC\User\Session $userSession) {
		$this->connection = $connection;
		$this->mountManager = $mountManager;
		$this->userSession = $userSession;
		$this->storageLoader = $storageLoader;
	}

	public function setup() {
		$user = $this->userSession->getUser();
		if ($user) {
			$query = $this->connection->prepare('SELECT `remote`, `token`, `password`, `mountpoint`, `owner`
			FROM *PREFIX*share_external WHERE `user` = ?');
			$query->execute(array($user->getUID()));

			while ($row = $query->fetch()) {
				$row['manager'] = $this;
				$mount = new Mount(self::STORAGE, $row['mountpoint'], $row, $this->storageLoader);
				$this->mountManager->addMount($mount);
			}
		}
	}

	/**
	 * @return \OC\Files\Mount\Manager
	 */
	public function getMountManager() {
		return $this->mountManager;
	}

	/**
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function setMountPoint($source, $target) {
		$sourceHash = md5($source);
		$targetHash = md5($target);

		$query = $this->connection->prepare('UPDATE *PREFIX*share_external SET
			`mountpoint` = ?, `mountpoint_hash` = ? WHERE `mountpoint_hash` = ?');
		$query->execute(array($target, $targetHash, $sourceHash));

		$mount = $this->mountManager->find($source);
		$mount->setMountPoint($target . '/');
		$this->mountManager->addMount($mount);
		$this->mountManager->removeMount($source . '/');
	}
}
