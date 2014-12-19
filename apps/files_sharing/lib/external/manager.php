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
	 * @var \OC\Files\Storage\StorageFactory
	 */
	private $storageLoader;

	/**
	 * @var \OC\User\Session
	 */
	private $userSession;

	/**
	 * @var \OC\HTTPHelper
	 */
	private $httpHelper;

	/**
	 * @param \OCP\IDBConnection $connection
	 * @param \OC\Files\Mount\Manager $mountManager
	 * @param \OC\User\Session $userSession
	 * @param \OC\Files\Storage\StorageFactory $storageLoader
	 */
	public function __construct(\OCP\IDBConnection $connection, \OC\Files\Mount\Manager $mountManager,
								\OC\Files\Storage\StorageFactory $storageLoader, \OC\User\Session $userSession, \OC\HTTPHelper $httpHelper) {
		$this->connection = $connection;
		$this->mountManager = $mountManager;
		$this->userSession = $userSession;
		$this->storageLoader = $storageLoader;
		$this->httpHelper = $httpHelper;
	}

	public function addShare($remote, $token, $password, $name, $owner, $accepted=false, $user = null, $remoteId = -1) {

		$user = $user ? $user: $this->userSession->getUser()->getUID();
		$accepted = $accepted ? 1 : 0;

		$mountPoint = Filesystem::normalizePath('/' . $name);

		$query = $this->connection->prepare('
				INSERT INTO `*PREFIX*share_external`
					(`remote`, `share_token`, `password`, `name`, `owner`, `user`, `mountpoint`, `mountpoint_hash`, `accepted`, `remote_id`)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			');
		$hash = md5($mountPoint);
		$query->execute(array($remote, $token, $password, $name, $owner, $user, $mountPoint, $hash, $accepted, $remoteId));

		if ($accepted) {
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

		$user = $this->userSession->getUser();
		if ($user) {
			$query = $this->connection->prepare('
				SELECT `remote`, `share_token`, `password`, `mountpoint`, `owner`
				FROM `*PREFIX*share_external`
				WHERE `user` = ? AND `accepted` = ?
			');
			$query->execute(array($user->getUID(), 1));

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
			SELECT `remote`, `share_token`
			FROM  `*PREFIX*share_external`
			WHERE `id` = ? AND `user` = ?');
		$result = $getShare->execute(array($id,  $this->userSession->getUser()->getUID()));

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
			$acceptShare = $this->connection->prepare('
				UPDATE `*PREFIX*share_external`
				SET `accepted` = ?
				WHERE `id` = ? AND `user` = ?');
			$acceptShare->execute(array(1, $id,  $this->userSession->getUser()->getUID()));
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $id, 'accept');
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
			$removeShare->execute(array($id, $this->userSession->getUser()->getUID()));
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $id, 'decline');
		}
	}

	/**
	 * inform remote server whether server-to-server share was accepted/declined
	 *
	 * @param string $remote
	 * @param string $token
	 * @param int $id
	 * @param string $feedback
	 * @return boolean
	 */
	private function sendFeedbackToRemote($remote, $token, $id, $feedback) {

		$url = $remote . \OCP\Share::BASE_PATH_TO_SHARE_API . '/' . $id . '/' . $feedback . '?format=' . \OCP\Share::RESPONSE_FORMAT;
		$fields = array('token' => $token);

		$result = $this->httpHelper->post($url, $fields);
		$status = json_decode($result['result'], true);

		return ($result['success'] && $status['ocs']['meta']['statuscode'] === 100);
	}

	public static function setup() {
		$externalManager = new \OCA\Files_Sharing\External\Manager(
				\OC::$server->getDatabaseConnection(),
				\OC\Files\Filesystem::getMountManager(),
				\OC\Files\Filesystem::getLoader(),
				\OC::$server->getUserSession(),
				\OC::$server->getHTTPHelper()
		);
		$externalManager->setupMounts();
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
		$user = $this->userSession->getUser();
		$data['manager'] = $this;
		$mountPoint = '/' . $user->getUID() . '/files' . $data['mountpoint'];
		$data['mountpoint'] = $mountPoint;
		$data['certificateManager'] = \OC::$server->getCertificateManager($user);
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
		$user = $this->userSession->getUser();
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
		$result = (bool)$query->execute(array($target, $targetHash, $sourceHash, $user->getUID()));

		return $result;
	}

	public function removeShare($mountPoint) {
		$user = $this->userSession->getUser();
		$mountPoint = $this->stripPath($mountPoint);
		$hash = md5($mountPoint);

		$getShare = $this->connection->prepare('
			SELECT `remote`, `share_token`, `remote_id`
			FROM  `*PREFIX*share_external`
			WHERE `mountpoint_hash` = ? AND `user` = ?');
		$result = $getShare->execute(array($hash, $user->getUID()));

		if ($result) {
			$share = $getShare->fetch();
			$this->sendFeedbackToRemote($share['remote'], $share['share_token'], $share['remote_id'], 'decline');
		}

		$query = $this->connection->prepare('
			DELETE FROM `*PREFIX*share_external`
			WHERE `mountpoint_hash` = ?
			AND `user` = ?
		');
		return (bool)$query->execute(array($hash, $user->getUID()));
	}

	/**
	 * return a list of shares which are not yet accepted by the user
	 *
	 * @return array list of open server-to-server shares
	 */
	public function getOpenShares() {
		$openShares = $this->connection->prepare('SELECT * FROM `*PREFIX*share_external` WHERE `accepted` = ? AND `user` = ?');
		$result = $openShares->execute(array(0, $this->userSession->getUser()->getUID()));

		return $result ? $openShares->fetchAll() : array();

	}
}