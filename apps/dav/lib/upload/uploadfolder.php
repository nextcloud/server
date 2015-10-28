<?php

namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\ICollection;

class UploadFolder implements ICollection {

	private $node;

	function __construct(Directory $node) {
		$this->node = $node;
	}

	function createFile($name, $data = null) {
		// TODO: verify name - should be a simple number
		$this->node->createFile($name, $data);
	}

	function createDirectory($name) {
		throw new Forbidden('Permission denied to create file (filename ' . $name . ')');
	}

	function getChild($name) {
		if ($name === '.file') {
			return new FutureFile($this->node, '.file');
		}
		return $this->node->getChild($name);
	}

	function getChildren() {
		$children = $this->node->getChildren();
		$children[] = new FutureFile($this->node, '.file');
		return $children;
	}

	function childExists($name) {
		if ($name === '.file') {
			return true;
		}
		return $this->node->childExists($name);
	}

	function delete() {
		$this->node->delete();
	}

	function getName() {
		return $this->node->getName();
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	function getLastModified() {
		return $this->node->getLastModified();
	}
}
