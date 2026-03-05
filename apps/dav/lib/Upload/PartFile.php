<?php

/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\IFile;

/**
 * This class represents an Upload part which is not present on the storage itself
 * but handled directly by external storage services like S3 with Multipart Upload
 */
class PartFile implements IFile {
	public function __construct(
		private Directory $root,
		private array $partInfo,
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
		throw new Forbidden('Permission denied to get this file');
	}

	public function getPath() {
		return $this->root->getFileInfo()->getInternalPath() . '/' . $this->partInfo['PartNumber'];
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
		return $this->partInfo['ETag'];
	}

	/**
	 * @inheritdoc
	 */
	public function getSize() {
		return $this->partInfo['Size'];
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
		return $this->partInfo['PartNumber'];
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
		return $this->partInfo['LastModified'];
	}
}
