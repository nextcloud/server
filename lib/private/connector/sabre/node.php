<?php

use Sabre\DAV\URLUtil;
use OC\Connector\Sabre\TagList;

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
abstract class OC_Connector_Sabre_Node implements \Sabre\DAV\INode, \Sabre\DAV\IProperties {
	const GETETAG_PROPERTYNAME = '{DAV:}getetag';
	const LASTMODIFIED_PROPERTYNAME = '{DAV:}lastmodified';

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

		list($parentPath,) = URLUtil::splitPath($this->path);
		list(, $newName) = URLUtil::splitPath($name);

		if (!\OCP\Util::isValidFileName($newName)) {
			throw new \Sabre\DAV\Exception\BadRequest();
		}

		$newPath = $parentPath . '/' . $newName;
		$oldPath = $this->path;

		$this->fileView->rename($this->path, $newPath);

		$this->path = $newPath;

		$query = OC_DB::prepare('UPDATE `*PREFIX*properties` SET `propertypath` = ?'
			. ' WHERE `userid` = ? AND `propertypath` = ?');
		$query->execute(array($newPath, OC_User::getUser(), $oldPath));
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
	 * Updates properties on this node,
	 * @see \Sabre\DAV\IProperties::updateProperties
	 * @param array $properties
	 * @return boolean
	 */
	public function updateProperties($properties) {
		$existing = $this->getProperties(array());
		foreach ($properties as $propertyName => $propertyValue) {
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if (array_key_exists($propertyName, $existing)) {
					$query = OC_DB::prepare('DELETE FROM `*PREFIX*properties`'
						. ' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?');
					$query->execute(array(OC_User::getUser(), $this->path, $propertyName));
				}
			} else {
				if (strcmp($propertyName, self::GETETAG_PROPERTYNAME) === 0) {
					\OC\Files\Filesystem::putFileInfo($this->path, array('etag' => $propertyValue));
				} elseif (strcmp($propertyName, self::LASTMODIFIED_PROPERTYNAME) === 0) {
					$this->touch($propertyValue);
				} else {
					if (!array_key_exists($propertyName, $existing)) {
						$query = OC_DB::prepare('INSERT INTO `*PREFIX*properties`'
							. ' (`userid`,`propertypath`,`propertyname`,`propertyvalue`) VALUES(?,?,?,?)');
						$query->execute(array(OC_User::getUser(), $this->path, $propertyName, $propertyValue));
					} else {
						$query = OC_DB::prepare('UPDATE `*PREFIX*properties` SET `propertyvalue` = ?'
							. ' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?');
						$query->execute(array($propertyValue, OC_User::getUser(), $this->path, $propertyName));
					}
				}
			}

		}
		$this->setPropertyCache(null);
		return true;
	}

	/**
	 * removes all properties for this node and user
	 */
	public function removeProperties() {
		$query = OC_DB::prepare('DELETE FROM `*PREFIX*properties`'
			. ' WHERE `userid` = ? AND `propertypath` = ?');
		$query->execute(array(OC_User::getUser(), $this->path));

		$this->setPropertyCache(null);
	}

	/**
	 * Returns a list of properties for this nodes.;
	 * @param array $properties
	 * @return array
	 * @note The properties list is a list of propertynames the client
	 * requested, encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author If the array is empty, all
	 * properties should be returned
	 */
	public function getProperties($properties) {

		if (is_null($this->property_cache)) {
			$sql = 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?';
			$result = OC_DB::executeAudited($sql, array(OC_User::getUser(), $this->path));

			$this->property_cache = array();
			while ($row = $result->fetchRow()) {
				$this->property_cache[$row['propertyname']] = $row['propertyvalue'];
			}

			$this->property_cache[self::GETETAG_PROPERTYNAME] = '"' . $this->info->getEtag() . '"';
		}

		// if the array was empty, we need to return everything
		if (count($properties) == 0) {
			return $this->property_cache;
		}

		$props = array();
		foreach ($properties as $property) {
			if (isset($this->property_cache[$property])) {
				$props[$property] = $this->property_cache[$property];
			}
		}

		return $props;
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
			$instanceId = OC_Util::getInstanceId();
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
}
