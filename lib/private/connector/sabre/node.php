<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Klaas Freitag <freitag@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Connector\Sabre;

use OC\Connector\Sabre\Exception\InvalidPath;


abstract class Node implements \Sabre\DAV\INode {
	/**
	 * Allow configuring the method used to generate Etags
	 *
	 * @var array(class_name, function_name)
	 */
	public static $ETagFunction = null;

	/**
	 * @var \OC\Files\View
	 */
	protected $fileView;

	/**
	 * The path to the current node
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * node properties cache
	 *
	 * @var array
	 */
	protected $property_cache = null;

	/**
	 * @var \OCP\Files\FileInfo
	 */
	protected $info;

	/**
	 * Sets up the node, expects a full path name
	 * @param \OC\Files\View $view
	 * @param \OCP\Files\FileInfo $info
	 */
	public function __construct($view, $info) {
		$this->fileView = $view;
		$this->path = $this->fileView->getRelativePath($info->getPath());
		$this->info = $info;
	}

	protected function refreshInfo() {
		$this->info = $this->fileView->getFileInfo($this->path);
	}

	/**
	 *  Returns the name of the node
	 * @return string
	 */
	public function getName() {
		return $this->info->getName();
	}

	/**
	 * Returns the full path
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Renames the node
	 * @param string $name The new name
	 * @throws \Sabre\DAV\Exception\BadRequest
	 * @throws \Sabre\DAV\Exception\Forbidden
	 */
	public function setName($name) {

		// rename is only allowed if the update privilege is granted
		if (!$this->info->isUpdateable()) {
			throw new \Sabre\DAV\Exception\Forbidden();
		}

		list($parentPath,) = \Sabre\HTTP\URLUtil::splitPath($this->path);
		list(, $newName) = \Sabre\HTTP\URLUtil::splitPath($name);

		// verify path of the target
		$this->verifyPath();

		$newPath = $parentPath . '/' . $newName;

		$this->fileView->rename($this->path, $newPath);

		$this->path = $newPath;

		$this->refreshInfo();
	}

	public function setPropertyCache($property_cache) {
		$this->property_cache = $property_cache;
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 * @return int timestamp as integer
	 */
	public function getLastModified() {
		$timestamp = $this->info->getMtime();
		if (!empty($timestamp)) {
			return (int)$timestamp;
		}
		return $timestamp;
	}

	/**
	 *  sets the last modification time of the file (mtime) to the value given
	 *  in the second parameter or to now if the second param is empty.
	 *  Even if the modification time is set to a custom value the access time is set to now.
	 */
	public function touch($mtime) {
		$this->fileView->touch($this->path, $mtime);
		$this->refreshInfo();
	}

	/**
	 * Returns the ETag for a file
	 *
	 * An ETag is a unique identifier representing the current version of the
	 * file. If the file changes, the ETag MUST change.  The ETag is an
	 * arbitrary string, but MUST be surrounded by double-quotes.
	 *
	 * Return null if the ETag can not effectively be determined
	 *
	 * @return string
	 */
	public function getETag() {
		return '"' . $this->info->getEtag() . '"';
	}

	/**
	 * Sets the ETag
	 *
	 * @param string $etag
	 *
	 * @return int file id of updated file or -1 on failure
	 */
	public function setETag($etag) {
		return $this->fileView->putFileInfo($this->path, array('etag' => $etag));
	}

	/**
	 * Returns the size of the node, in bytes
	 *
	 * @return int|float
	 */
	public function getSize() {
		return $this->info->getSize();
	}

	/**
	 * Returns the cache's file id
	 *
	 * @return int
	 */
	public function getId() {
		return $this->info->getId();
	}

	/**
	 * @return string|null
	 */
	public function getFileId() {
		if ($this->info->getId()) {
			$instanceId = \OC_Util::getInstanceId();
			$id = sprintf('%08d', $this->info->getId());
			return $id . $instanceId;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getDavPermissions() {
		$p ='';
		if ($this->info->isShared()) {
			$p .= 'S';
		}
		if ($this->info->isShareable()) {
			$p .= 'R';
		}
		if ($this->info->isMounted()) {
			$p .= 'M';
		}
		if ($this->info->isDeletable()) {
			$p .= 'D';
		}
		if ($this->info->isDeletable()) {
			$p .= 'NV'; // Renameable, Moveable
		}
		if ($this->info->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
			if ($this->info->isUpdateable()) {
				$p .= 'W';
			}
		} else {
			if ($this->info->isCreatable()) {
				$p .= 'CK';
			}
		}
		return $p;
	}

	protected function verifyPath() {
		try {
			$fileName = basename($this->info->getPath());
			$this->fileView->verifyPath($this->path, $fileName);
		} catch (\OCP\Files\InvalidPathException $ex) {
			throw new InvalidPath($ex->getMessage());
		}
	}
}
