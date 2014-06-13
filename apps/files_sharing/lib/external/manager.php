<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;

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

	public function addShare($remote, $token, $password, $name, $owner) {
		$user = $this->userSession->getUser();
		if ($user) {
			$query = $this->connection->prepare('INSERT INTO *PREFIX*share_external(`remote`, `share_token`, `password`,
				`name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
			$mountPoint = Filesystem::normalizePath('/' . $name);
			$hash = md5($mountPoint);
			$query->execute(array($remote, $token, $password, $name, $owner, $user->getUID(), $mountPoint, $hash));

			$options = array(
				'remote' => $remote,
				'token' => $token,
				'password' => $password,
				'mountpoint' => $mountPoint,
				'owner' => $owner
			);
			return $this->mountShare($options);
		}
	}

	public function setup() {
		// don't setup server-to-server shares if the file_external app is disabled
		// FIXME no longer needed if we use the webdav implementation from  core
		if (\OC_App::isEnabled('files_external') === false) {
			return false;
		}

		$user = $this->userSession->getUser();
		if ($user) {
			$query = $this->connection->prepare('SELECT `remote`, `share_token`, `password`, `mountpoint`, `owner`
			FROM *PREFIX*share_external WHERE `user` = ?');
			$query->execute(array($user->getUID()));

			while ($row = $query->fetch()) {
				$row['manager'] = $this;
				$row['token'] = $row['share_token'];
				$this->mountShare($row);
			}
		}
	}

	protected function stripPath($path) {
		$prefix = '/' . $this->userSession->getUser()->getUID() . '/files';
		return rtrim(substr($path, strlen($prefix)), '/');
	}

	/**
	 * @param array $data
	 * @return Mount
	 */
	protected function mountShare($data) {
		$mountPoint = '/' . $this->userSession->getUser()->getUID() . '/files' . $data['mountpoint'];
		$mount = new Mount(self::STORAGE, $mountPoint, $data, $this, $this->storageLoader);
		$this->mountManager->addMount($mount);
		return $mount;
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
		$source = $this->stripPath($source);
		$target = $this->stripPath($target);
		$sourceHash = md5($source);
		$targetHash = md5($target);

		$query = $this->connection->prepare('UPDATE *PREFIX*share_external SET
			`mountpoint` = ?, `mountpoint_hash` = ? WHERE `mountpoint_hash` = ?');
		$result = (bool)$query->execute(array($target, $targetHash, $sourceHash));

		return $result;
	}

	public function removeShare($mountPoint) {
		$mountPoint = $this->stripPath($mountPoint);
		$hash = md5($mountPoint);
		$query = $this->connection->prepare('DELETE FROM *PREFIX*share_external WHERE `mountpoint_hash` = ?');
		return (bool)$query->execute(array($hash));
	}
}
