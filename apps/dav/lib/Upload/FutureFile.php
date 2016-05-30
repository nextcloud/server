<?php

namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\AssemblyStream;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\IFile;

/**
 * Class FutureFile
 *
 * The FutureFile is a SabreDav IFile which connects the chunked upload directory
 * with the AssemblyStream, who does the final assembly job
 *
 * @package OCA\DAV\Upload
 */
class FutureFile implements \Sabre\DAV\IFile {

	/** @var Directory */
	private $root;
	/** @var string */
	private $name;

	/**
	 * @param Directory $root
	 * @param string $name
	 */
	function __construct(Directory $root, $name) {
		$this->root = $root;
		$this->name = $name;
	}

	/**
	 * @inheritdoc
	 */
	function put($data) {
		throw new Forbidden('Permission denied to put into this file');
	}

	/**
	 * @inheritdoc
	 */
	function get() {
		$nodes = $this->root->getChildren();
		return AssemblyStream::wrap($nodes);
	}

	/**
	 * @inheritdoc
	 */
	function getContentType() {
		return 'application/octet-stream';
	}

	/**
	 * @inheritdoc
	 */
	function getETag() {
		return $this->root->getETag();
	}

	/**
	 * @inheritdoc
	 */
	function getSize() {
		$children = $this->root->getChildren();
		$sizes = array_map(function($node) {
			/** @var IFile $node */
			return $node->getSize();
		}, $children);

		return array_sum($sizes);
	}

	/**
	 * @inheritdoc
	 */
	function delete() {
		$this->root->delete();
	}

	/**
	 * @inheritdoc
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	function setName($name) {
		throw new Forbidden('Permission denied to rename this file');
	}

	/**
	 * @inheritdoc
	 */
	function getLastModified() {
		return $this->root->getLastModified();
	}
}
