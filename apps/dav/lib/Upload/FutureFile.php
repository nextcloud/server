<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
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
	/**
	 * @param Directory $root
	 * @param string $name
	 */
	public function __construct(
		private Directory $root,
		private $name,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function put($data) {
		throw new Forbidden('Permission denied to put into this file');
	}

	/**
	 * @inheritdoc
	 */
	public function get() {
		$nodes = $this->root->getChildren();
		return AssemblyStream::wrap($nodes);
	}

	public function getPath() {
		return $this->root->getFileInfo()->getInternalPath() . '/.file';
	}

	/**
	 * @inheritdoc
	 */
	public function getContentType() {
		return 'application/octet-stream';
	}

	/**
	 * @inheritdoc
	 */
	public function getETag() {
		return $this->root->getETag();
	}

	/**
	 * @inheritdoc
	 */
	public function getSize() {
		$children = $this->root->getChildren();
		$sizes = array_map(function ($node) {
			/** @var IFile $node */
			return $node->getSize();
		}, $children);

		return array_sum($sizes);
	}

	/**
	 * @inheritdoc
	 */
	public function delete() {
		$this->root->delete();
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @inheritdoc
	 */
	public function setName($name) {
		throw new Forbidden('Permission denied to rename this file');
	}

	/**
	 * @inheritdoc
	 */
	public function getLastModified() {
		return $this->root->getLastModified();
	}
}
