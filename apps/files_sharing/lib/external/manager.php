<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OCP\Files;

class Manager {
	const STORAGE = '\OCA\Files_Sharing\External\Storage';

	/**
	 * @var string
	 */
	private $uid;

	/**
	 * @var \OCP\IDBConnection
	 */
	private $connection;

	/**
	 * @var \OC\Files\Mount\Manager
	 */
	private $mountManager;

	/**
	 * @var \OCP\Files\Storage\IStorageFactory
	 */
	private $storageLoader;

	/**
	 * @var \OC\HTTPHelper
	 */
	private $httpHelper;

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OC\Files\Mount\Manager $mountManager
	 * @param \OCP\Files\Storage\IStorageFactory $storageLoader
	 * @param \OC\HTTPHelper $httpHelper
	 * @param string $uid
	 */
	public function __construct(\OCP\IDBConnection $connection, \OC\Files\Mount\Manager $mountManager,
								\OCP\Files\Storage\IStorageFactory $storageLoader, \OC\HTTPHelper $httpHelper, $uid) {
		$this->connection = $connection;
		$this->mountManager = $mountManager;
		$this->storageLoader = $storageLoader;
		$this->httpHelper = $httpHelper;
		$this->uid = $uid;
	}

	/**
	 * add new server-to-server share
	 *
	 * @param string $remote
	 * @param string $token
	 * @param string $password
	 * @param string $name
	 * @param string $owner
	 * @param boolean $accepted
	 * @param string $user
	 * @param int $remoteId
	 * @return Mount|null
	 */
	public function addShare($remote, $token, $password, $name, $owner, $accepted=false, $user = null, $remoteId = -1) {

		$user = $user ? $user : $this->uid;
		$accepted = $accepted ? 1 : 0;
		$name = Filesystem::normalizePath('/' . $name);

		if (!$accepted) {
			// To avoid conflicts with the mount point generation later,
			// we only use a temporary mount point name here. The real
			// mount point name will be generated when accepting the share,
			// using the original share item name.
			$tmpMountPointName = '{{TemporaryMountPointName#' . $name . '}}';
			$mountPoint = $tmpMountPointName;
			$hash = md5($tmpMountPointName);
			$data = [
				'remote'		=> $remote,
				'share_token'	=> $token,
				'password'		=> $password,
				'name'			=> $name,
				'owner'			=> $owner,
				'user'			=> $user,
				'mountpoint'	=> $mountPoint,
				'mountpoint_hash'	=> $hash,
				'accepted'		=> $accepted,
				'remote_id'		=> $remoteId,
			];

			$i = 1;
			while (!$this->connection->insertIfNotExist('*PREFIX*share_external', $data, ['user', 'mountpoint_hash'])) {
				// The external share already exists for the user
				$data['mountpoint'] = $tmpMountPointName . '-' . $i;
				$data['mountpoint_hash'] = md5($data['mountpoint']);
				$i++;
			}
			return null;
		}

		$mountPoint = Files::buildNotExistingFileName('/', $name);
		$mountPoint = Filesystem::normalizePath('/' . $mountPoint);
		$hash = md5($mountPoint);

		$query = $this->connection->prepare('
				INSERT INTO `*PREFIX*share_external`
					(`remote`, `share_token`, `password`, `name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`, `accepted`, `remote_id`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			');
		$query->execute(array($remote, $token, $password, $name, $owner, $user, $mountPoint, $hash, $accepted, $remoteId));

		$options = array(
			'remote'	=> $remote,
			'token'		=> $token,
			'password'	=> $password,
			'mountpoint'	=> $mountPoint,
			'owner'		=> $owner
		);
		return $this->mountShare($options);
	}

	private function setupMounts() {
		// don't setup server-to-server shares if the admin disabled it
		if (\OCA\Files_Sharing\Helper::isIncomingServer2serverShareEnabled() === false) {
			return false;
		}

		if (!is_null($this->uid)) {
			$query = $this->connection->prepare('
				SELECT `remote`, `share_token`, `password`, `mountpoint`, `owner`
				FROM `*PREFIX*share_external`
				WHERE `user` = ? AND `accepted` = ?
			');
			$query->execute(array($this->uid, 1));

			while ($row = $query->fetch()) {
				$row['manager'] = $this;
				$row['token'] = $row['share_token'];
				$this->mountShare($row);
			}
		}
	}

	/**
	 * get share
	 *
	 * @param int $id share id
	 * @return mixed share of false
	 */
	private function getShare($id) {
		$getShare = $this->connection->prepare('
			SELECT `remote`, `remote_id`, `share_token`, `name`
			FROM  `*PREFIX*share_external`
			WHERE `id` = ? AND `user` = ?');
		$result = $getShare->execute(array($id, $this->uid));

		return $result ? $getShare->fetch() : false;
	}

	/**
	 * accept server-to-server share
	 *
	 * @param int $id
	 */
	public function acceptShare($id) {

		$share = $this->getShare($id);

		if ($share) {
			$mountPoint = Files::buildNotExistingFileName('/', $share['name']);
			$mountPoint = Filesystem::normalizePath('/' . $mountPoint);
			$hash = md5($mountPoint);

			$acceptShare = $this->connection->prepare('
				UPDATE `*PREFIX*share_external`
				SET `accepted` = ?,
					`mountpoint` = ?,
					`mountpoint_hash` = ?
				WHERE `id` = ? AND `user` = ?');
			$acceptShare->execute(array(1, $mountPoint, $hash, $id, $this->uid));
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $share['remote_id'], 'accept');
		}
	}

	/**
	 * decline server-to-server share
	 *
	 * @param int $id
	 */
	public function declineShare($id) {

		$share = $this->getShare($id);

		if ($share) {
			$removeShare = $this->connection->prepare('
				DELETE FROM `*PREFIX*share_external` WHERE `id` = ? AND `user` = ?');
			$removeShare->execute(array($id, $this->uid));
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $share['remote_id'], 'decline');
		}
	}

	/**
	 * inform remote server whether server-to-server share was accepted/declined
	 *
	 * @param string $remote
	 * @param string $token
	 * @param int $remoteId Share id on the remote host
	 * @param string $feedback
	 * @return boolean
	 */
	private function sendFeedbackToRemote($remote, $token, $remoteId, $feedback) {

		$url = rtrim($remote, '/') . \OCP\Share::BASE_PATH_TO_SHARE_API . '/' . $remoteId . '/' . $feedback . '?format=' . \OCP\Share::RESPONSE_FORMAT;
		$fields = array('token' => $token);

		$result = $this->httpHelper->post($url, $fields);
		$status = json_decode($result['result'], true);

		return ($result['success'] && $status['ocs']['meta']['statuscode'] === 100);
	}

	/**
	 * setup the server-to-server mounts
	 *
	 * @param array $params
	 */
	public static function setup(array $params) {
		$externalManager = new \OCA\Files_Sharing\External\Manager(
				\OC::$server->getDatabaseConnection(),
				\OC\Files\Filesystem::getMountManager(),
				\OC\Files\Filesystem::getLoader(),
				\OC::$server->getHTTPHelper(),
				$params['user']
		);

		$externalManager->setupMounts();
	}

	/**
	 * remove '/user/files' from the path and trailing slashes
	 *
	 * @param string $path
	 * @return string
	 */
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
		$data['certificateManager'] = \OC::$server->getCertificateManager($this->uid);
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

		$getShare = $this->connection->prepare('
			SELECT `remote`, `share_token`, `remote_id`
			FROM  `*PREFIX*share_external`
			WHERE `mountpoint_hash` = ? AND `user` = ?');
		$result = $getShare->execute(array($hash, $this->uid));

		if ($result) {
			$share = $getShare->fetch();
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $share['remote_id'], 'decline');
		}

		$query = $this->connection->prepare('
			DELETE FROM `*PREFIX*share_external`
			WHERE `mountpoint_hash` = ?
			AND `user` = ?
		');
		return (bool)$query->execute(array($hash, $this->uid));
	}

	/**
	 * remove all shares for user $uid if the user was deleted
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function removeUserShares($uid) {
		$getShare = $this->connection->prepare('
			SELECT `remote`, `share_token`, `remote_id`
			FROM  `*PREFIX*share_external`
			WHERE `user` = ?');
		$result = $getShare->execute(array($uid));

		if ($result) {
			$shares = $getShare->fetchAll();
			foreach($shares as $share) {
				$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $share['remote_id'], 'decline');
			}
		}

		$query = $this->connection->prepare('
			DELETE FROM `*PREFIX*share_external`
			WHERE `user` = ?
		');
		return (bool)$query->execute(array($uid));
	}

	/**
	 * return a list of shares which are not yet accepted by the user
	 *
	 * @return array list of open server-to-server shares
	 */
	public function getOpenShares() {
		return $this->getShares(false);
	}

	/**
	 * return a list of shares for the user
	 *
	 * @param bool|null $accepted True for accepted only,
	 *                            false for not accepted,
	 *                            null for all shares of the user
	 * @return array list of open server-to-server shares
	 */
	private function getShares($accepted) {
		$query = 'SELECT * FROM `*PREFIX*share_external` WHERE `user` = ?';
		$parameters = [$this->uid];
		if (!is_null($accepted)) {
			$query .= ' AND `accepted` = ?';
			$parameters[] = (int) $accepted;
		}
		$query .= ' ORDER BY `id` ASC';

		$shares = $this->connection->prepare($query);
		$result = $shares->execute($parameters);

		return $result ? $shares->fetchAll() : [];
	}
}
