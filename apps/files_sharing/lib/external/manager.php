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
	 * @var string
	 */
	private $uid;

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OC\Files\Mount\Manager $mountManager
	 * @param \OC\Files\Storage\Loader $storageLoader
	 * @param string $uid
	 */
	public function __construct(\OCP\IDBConnection $connection, \OC\Files\Mount\Manager $mountManager,
								\OC\Files\Storage\Loader $storageLoader, $uid) {
		$this->connection = $connection;
		$this->mountManager = $mountManager;
		$this->storageLoader = $storageLoader;
		$this->uid = $uid;
	}

	public function addShare($remote, $token, $password, $name, $owner) {
		if ($this->uid) {
			$query = $this->connection->prepare('
				INSERT INTO `*PREFIX*share_external`
					(`remote`, `share_token`, `password`, `name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)
			');
			$mountPoint = Filesystem::normalizePath('/' . $name);
			$hash = md5($mountPoint);
			$query->execute(array($remote, $token, $password, $name, $owner, $this->uid, $mountPoint, $hash));

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

	private function setupMounts() {
		// don't setup server-to-server shares if the admin disabled it
		if (\OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled() === false) {
			return false;
		}

		if ($this->uid) {
			$query = $this->connection->prepare('
				SELECT `remote`, `share_token`, `password`, `mountpoint`, `owner`
				FROM `*PREFIX*share_external`
				WHERE `user` = ?
			');
			$query->execute(array($this->uid));

			while ($row = $query->fetch()) {
				$row['manager'] = $this;
				$row['token'] = $row['share_token'];
				$this->mountShare($row);
			}
		}
	}

	public static function setup($param) {
		$externalManager = new \OCA\Files_Sharing\External\Manager(
			\OC::$server->getDatabaseConnection(),
			\OC\Files\Filesystem::getMountManager(),
			\OC\Files\Filesystem::getLoader(),
			$param['user']
		);
		$externalManager->setupMounts();
	}

	protected function stripPath($path) {
		$prefix = '/' . $this->uid . '/files';
		return rtrim(substr($path, strlen($prefix)), '/');
	}

	/**
	 * @param array $data
	 * @return Mount
	 */
	protected function mountShare($data) {
		$data['manager'] = $this;
		$mountPoint = '/' . $this->uid . '/files' . $data['mountpoint'];
		$data['mountpoint'] = $mountPoint;
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

		$query = $this->connection->prepare('
			UPDATE `*PREFIX*share_external`
			SET `mountpoint` = ?, `mountpoint_hash` = ?
			WHERE `mountpoint_hash` = ?
			AND `user` = ?
		');
		$result = (bool)$query->execute(array($target, $targetHash, $sourceHash, $this->uid));

		return $result;
	}

	public function removeShare($mountPoint) {
		$mountPoint = $this->stripPath($mountPoint);
		$hash = md5($mountPoint);
		$query = $this->connection->prepare('
			DELETE FROM `*PREFIX*share_external`
			WHERE `mountpoint_hash` = ?
			AND `user` = ?
		');
		return (bool)$query->execute(array($hash, $this->uid));
	}
}
