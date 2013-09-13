<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Node;

use OC\Files\Cache\Cache;
use OC\Files\Cache\Scanner;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

class Node implements \OCP\Files\Node {
	/**
	 * @var \OC\Files\View $view
	 */
	protected $view;

	/**
	 * @var \OC\Files\Node\Root $root
	 */
	protected $root;

	/**
	 * @var string $path
	 */
	protected $path;

	/**
	 * @param \OC\Files\View $view
	 * @param \OC\Files\Node\Root Root $root
	 * @param string $path
	 */
	public function __construct($root, $view, $path) {
		$this->view = $view;
		$this->root = $root;
		$this->path = $path;
	}

	/**
	 * @param string[] $hooks
	 */
	protected function sendHooks($hooks) {
		foreach ($hooks as $hook) {
			$this->root->emit('\OC\Files', $hook, array($this));
		}
	}

	/**
	 * @param int $permissions
	 * @return bool
	 */
	protected function checkPermissions($permissions) {
		return ($this->getPermissions() & $permissions) === $permissions;
	}

	/**
	 * @param string $targetPath
	 * @throws \OCP\Files\NotPermittedException
	 * @return \OC\Files\Node\Node
	 */
	public function move($targetPath) {
		return;
	}

	public function delete() {
		return;
	}

	/**
	 * @param string $targetPath
	 * @return \OC\Files\Node\Node
	 */
	public function copy($targetPath) {
		return;
	}

	/**
	 * @param int $mtime
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function touch($mtime = null) {
		if ($this->checkPermissions(\OCP\PERMISSION_UPDATE)) {
			$this->sendHooks(array('preTouch'));
			$this->view->touch($this->path, $mtime);
			$this->sendHooks(array('postTouch'));
		} else {
			throw new NotPermittedException();
		}
	}

	/**
	 * @return \OC\Files\Storage\Storage
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getStorage() {
		list($storage,) = $this->view->resolvePath($this->path);
		return $storage;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getInternalPath() {
		list(, $internalPath) = $this->view->resolvePath($this->path);
		return $internalPath;
	}

	/**
	 * @return int
	 */
	public function getId() {
		$info = $this->view->getFileInfo($this->path);
		return $info['fileid'];
	}

	/**
	 * @return array
	 */
	public function stat() {
		return $this->view->stat($this->path);
	}

	/**
	 * @return int
	 */
	public function getMTime() {
		return $this->view->filemtime($this->path);
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return $this->view->filesize($this->path);
	}

	/**
	 * @return string
	 */
	public function getEtag() {
		$info = $this->view->getFileInfo($this->path);
		return $info['etag'];
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		$info = $this->view->getFileInfo($this->path);
		return $info['permissions'];
	}

	/**
	 * @return bool
	 */
	public function isReadable() {
		return $this->checkPermissions(\OCP\PERMISSION_READ);
	}

	/**
	 * @return bool
	 */
	public function isUpdateable() {
		return $this->checkPermissions(\OCP\PERMISSION_UPDATE);
	}

	/**
	 * @return bool
	 */
	public function isDeletable() {
		return $this->checkPermissions(\OCP\PERMISSION_DELETE);
	}

	/**
	 * @return bool
	 */
	public function isShareable() {
		return $this->checkPermissions(\OCP\PERMISSION_SHARE);
	}

	/**
	 * @return Node
	 */
	public function getParent() {
		return $this->root->get(dirname($this->path));
	}

	/**
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function normalizePath($path) {
		if ($path === '' or $path === '/') {
			return '/';
		}
		//no windows style slashes
		$path = str_replace('\\', '/', $path);
		//add leading slash
		if ($path[0] !== '/') {
			$path = '/' . $path;
		}
		//remove duplicate slashes
		while (strpos($path, '//') !== false) {
			$path = str_replace('//', '/', $path);
		}
		//remove trailing slash
		$path = rtrim($path, '/');

		return $path;
	}

	/**
	 * check if the requested path is valid
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isValidPath($path) {
		if (!$path || $path[0] !== '/') {
			$path = '/' . $path;
		}
		if (strstr($path, '/../') || strrchr($path, '/') === '/..') {
			return false;
		}
		return true;
	}
}
