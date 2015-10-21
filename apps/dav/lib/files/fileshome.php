<?php

namespace OCA\DAV\Files;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;
use Sabre\DAV\SimpleCollection;
use Sabre\HTTP\URLUtil;

class FilesHome implements ICollection {

	/**
	 * FilesHome constructor.
	 *
	 * @param array $principalInfo
	 */
	public function __construct($principalInfo) {
		$this->principalInfo = $principalInfo;
	}

	function createFile($name, $data = null) {
		return $this->impl()->createFile($name, $data);
	}

	function createDirectory($name) {
		$this->impl()->createDirectory($name);
	}

	function getChild($name) {
		return $this->impl()->getChild($name);
	}

	function getChildren() {
		return $this->impl()->getChildren();
	}

	function childExists($name) {
		return $this->impl()->childExists($name);
	}

	function delete() {
		$this->impl()->delete();
	}

	function getName() {
		list(,$name) = URLUtil::splitPath($this->principalInfo['uri']);
		return $name;
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	function getLastModified() {
		return $this->impl()->getLastModified();
	}

	/**
	 * @return Directory
	 */
	private function impl() {
		//
		// TODO: we need to mount filesystem of the give user
		//
		$user = \OC::$server->getUserSession()->getUser();
		if ($this->getName() !== $user->getUID()) {
			return new SimpleCollection($this->getName());
		}
		$view = \OC\Files\Filesystem::getView();
		$rootInfo = $view->getFileInfo('');
		$impl = new Directory($view, $rootInfo);
		return $impl;
	}
}
