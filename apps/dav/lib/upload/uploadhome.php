<?php

namespace OCA\DAV\Upload;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class UploadHome implements ICollection {
	/**
	 * FilesHome constructor.
	 *
	 * @param array $principalInfo
	 */
	public function __construct($principalInfo) {
		$this->principalInfo = $principalInfo;
	}

	function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	function createDirectory($name) {
		$this->impl()->createDirectory($name);
	}

	function getChild($name) {
		return new UploadFolder($this->impl()->getChild($name));
	}

	function getChildren() {
		return array_map(function($node) {
			return new UploadFolder($node);
		}, $this->impl()->getChildren());
	}

	function childExists($name) {
		return !is_null($this->getChild($name));
	}

	function delete() {
		$this->impl()->delete();
	}

	function getName() {
		return 'uploads';
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	function getLastModified() {
		return $this->impl()->getLastModified();
	}

	/**
	 * @return Directory
	 */
	private function impl() {
		$rootView = new View();
		$user = \OC::$server->getUserSession()->getUser();
		Filesystem::initMountPoints($user->getUID());
		if (!$rootView->file_exists('/' . $user->getUID() . '/uploads')) {
			$rootView->mkdir('/' . $user->getUID() . '/uploads');
		}
		$view = new View('/' . $user->getUID() . '/uploads');
		$rootInfo = $view->getFileInfo('');
		$impl = new Directory($view, $rootInfo);
		return $impl;
	}
}
