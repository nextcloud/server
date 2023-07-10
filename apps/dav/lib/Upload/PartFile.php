<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
	/** @var Directory */
	private $root;
	/** @var array */
	private $partInfo;

	public function __construct(Directory $root, array $partInfo) {
		$this->root = $root;
		$this->partInfo = $partInfo;
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
