<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OC\Files\Storage\DAV;
use OC\ForbiddenException;
use OCA\Files_Sharing\ISharedStorage;
use OCP\Files\NotFoundException;
use OCP\Files\StorageInvalidException;
use OCP\Files\StorageNotAvailableException;

class Storage extends DAV implements ISharedStorage {
	/**
	 * @var string
	 */
	private $remoteUser;

	/**
	 * @var string
	 */
	private $remote;

	/**
	 * @var string
	 */
	private $mountPoint;

	/**
	 * @var string
	 */
	private $token;

	/**
	 * @var \OCP\ICertificateManager
	 */
	private $certificateManager;

	private $updateChecked = false;

	/**
	 * @var \OCA\Files_Sharing\External\Manager
	 */
	private $manager;

	public function __construct($options) {
		$this->manager = $options['manager'];
		$this->certificateManager = $options['certificateManager'];
		$this->remote = $options['remote'];
		$this->remoteUser = $options['owner'];
		list($protocol, $remote) = explode('://', $this->remote);
		if (strpos($remote, '/')) {
			list($host, $root) = explode('/', $remote, 2);
		} else {
			$host = $remote;
			$root = '';
		}
		$secure = $protocol === 'https';
		$root = rtrim($root, '/') . '/public.php/webdav';
		$this->mountPoint = $options['mountpoint'];
		$this->token = $options['token'];
		parent::__construct(array(
			'secure' => $secure,
			'host' => $host,
			'root' => $root,
			'user' => $options['token'],
			'password' => $options['password']
		));
	}

	public function getRemoteUser() {
		return $this->remoteUser;
	}

	public function getRemote() {
		return $this->remote;
	}

	public function getMountPoint() {
		return $this->mountPoint;
	}

	public function getToken() {
		return $this->token;
	}

	public function getPassword() {
		return $this->password;
	}

	/**
	 * @brief get id of the mount point
	 * @return string
	 */
	public function getId() {
		return 'shared::' . md5($this->token . '@' . $this->remote);
	}

	public function getCache($path = '', $storage = null) {
		if (is_null($this->cache)) {
			$this->cache = new Cache($this, $this->remote, $this->remoteUser);
		}
		return $this->cache;
	}

	/**
	 * @param string $path
	 * @param \OC\Files\Storage\Storage $storage
	 * @return \OCA\Files_Sharing\External\Scanner
	 */
	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		if (!isset($this->scanner)) {
			$this->scanner = new Scanner($storage);
		}
		return $this->scanner;
	}

	/**
	 * check if a file or folder has been updated since $time
	 *
	 * @param string $path
	 * @param int $time
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @throws \OCP\Files\StorageInvalidException
	 * @return bool
	 */
	public function hasUpdated($path, $time) {
		// since for owncloud webdav servers we can rely on etag propagation we only need to check the root of the storage
		// because of that we only do one check for the entire storage per request
		if ($this->updateChecked) {
			return false;
		}
		$this->updateChecked = true;
		try {
			return parent::hasUpdated('', $time);
		} catch (StorageInvalidException $e) {
			// check if it needs to be removed
			$this->checkStorageAvailability();
			throw $e;
		} catch (StorageNotAvailableException $e) {
			// check if it needs to be removed or just temp unavailable
			$this->checkStorageAvailability();
			throw $e;
		}
	}

	/**
	 * Check whether this storage is permanently or temporarily
	 * unavailable
	 *
	 * @throws \OCP\Files\StorageNotAvailableException
	 * @throws \OCP\Files\StorageInvalidException
	 */
	public function checkStorageAvailability() {
		// see if we can find out why the share is unavailable
		try {
			$this->getShareInfo();
		} catch (NotFoundException $e) {
			// a 404 can either mean that the share no longer exists or there is no ownCloud on the remote
			if ($this->testRemote()) {
				// valid ownCloud instance means that the public share no longer exists
				// since this is permanent (re-sharing the file will create a new token)
				// we remove the invalid storage
				$this->manager->removeShare($this->mountPoint);
				$this->manager->getMountManager()->removeMount($this->mountPoint);
				throw new StorageInvalidException();
			} else {
				// ownCloud instance is gone, likely to be a temporary server configuration error
				throw $e;
			}
		} catch (ForbiddenException $e) {
			// auth error, remove share for now (provide a dialog in the future)
			$this->manager->removeShare($this->mountPoint);
			$this->manager->getMountManager()->removeMount($this->mountPoint);
			throw new StorageInvalidException();
		} catch (\Exception $e) {
			throw $e;
		}
	}

	public function file_exists($path) {
		if ($path === '') {
			return true;
		} else {
			return parent::file_exists($path);
		}
	}

	/**
	 * check if the configured remote is a valid ownCloud instance
	 *
	 * @return bool
	 */
	protected function testRemote() {
		try {
			$result = file_get_contents($this->remote . '/status.php');
			$data = json_decode($result);
			return is_object($data) and !empty($data->version);
		} catch (\Exception $e) {
			return false;
		}
	}

	public function getShareInfo() {
		$remote = $this->getRemote();
		$token = $this->getToken();
		$password = $this->getPassword();
		$url = rtrim($remote, '/') . '/index.php/apps/files_sharing/shareinfo?t=' . $token;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			http_build_query(array('password' => $password)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		$path = $this->certificateManager->getCertificateBundle();
		if (is_readable($path)) {
			curl_setopt($ch, CURLOPT_CAINFO, $path);
		}

		$result = curl_exec($ch);

		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errorMessage = curl_error($ch);
		curl_close($ch);
		if (!empty($errorMessage)) {
			throw new \Exception($errorMessage);
		}

		switch ($status) {
			case 401:
			case 403:
				throw new ForbiddenException();
			case 404:
				throw new NotFoundException();
			case 500:
				throw new \Exception();
		}

		return json_decode($result, true);
	}
}
