<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Filesystem;
use OCA\Files_Sharing\ISharedStorage;

class Storage extends \OC\Files\Storage\DAV implements ISharedStorage {
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
	 * @var \OCA\Files_Sharing\External\Manager
	 */
	private $manager;

	public function __construct($options) {
		$this->remote = $options['remote'];
		$this->remoteUser = $options['owner'];
		$this->manager = $options['manager'];
		list($protocol, $remote) = explode('://', $this->remote);
		list($host, $root) = explode('/', $remote);
		$secure = $protocol === 'https';
		$root .= '/public.php/webdav';
		$this->mountPoint = $options['mountpoint'];
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

	/**
	 * @brief get id of the mount point
	 * @return string
	 */
	public function getId() {
		return 'shared::' . md5($this->user . '@' . $this->remote);
	}

	public function getCache($path = '') {
		if (!isset($this->cache)) {
			$this->cache = new Cache($this, $this->remote, $this->remoteUser);
		}
		return $this->cache;
	}

	public function rename($path1, $path2) {
		// if we renamed the mount point we need to adjust the mountpoint in the database
		if (Filesystem::normalizePath($this->mountPoint) === Filesystem::normalizePath($path1)) {
			$this->manager->setMountPoint($path1, $path2);
			$this->mountPoint = $path2;
			return true;
		} else {
			// read only shares
			return false;
		}
	}

	public function unlink($path) {
		if ($path === '' || $path === false) {
			$this->manager->removeShare($this->mountPoint);
			return true;
		} else {
			return parent::unlink($path);
		}
	}

	public function rmdir($path) {
		if ($path === '' || $path === false) {
			$this->manager->removeShare($this->mountPoint);
			return true;
		} else {
			return parent::rmdir($path);
		}
	}
}
