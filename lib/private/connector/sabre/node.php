<?php

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

abstract class OC_Connector_Sabre_Node implements Sabre_DAV_INode, Sabre_DAV_IProperties {
	const GETETAG_PROPERTYNAME = '{DAV:}getetag';
	const LASTMODIFIED_PROPERTYNAME = '{DAV:}lastmodified';

	/**
	 * Allow configuring the method used to generate Etags
	 *
	 * @var array(class_name, function_name)
	*/
	public static $ETagFunction = null;

	/**
	 * is kept public to allow overwrite for unit testing
	 *
	 * @var \OC\Files\View
	 */
	public $fileView;

	/**
	 * The path to the current node
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * node fileinfo cache
	 * @var array
	 */
	protected $fileinfo_cache;
	/**
	 * node properties cache
	 * @var array
	 */
	protected $property_cache = null;

	/**
	 * @brief Sets up the node, expects a full path name
	 * @param string $path
	 * @return void
	 */
	public function __construct($path) {
		$this->path = $path;
	}



	/**
	 * @brief  Returns the name of the node
	 * @return string
	 */
	public function getName() {

		list(, $name)  = Sabre_DAV_URLUtil::splitPath($this->path);
		return $name;

	}

	/**
	 * @brief Renames the node
	 * @param string $name The new name
	 * @return void
	 */
	public function setName($name) {

		// rename is only allowed if the update privilege is granted
		if (!\OC\Files\Filesystem::isUpdatable($this->path)) {
			throw new \Sabre_DAV_Exception_Forbidden();
		}

		list($parentPath, ) = Sabre_DAV_URLUtil::splitPath($this->path);
		list(, $newName) = Sabre_DAV_URLUtil::splitPath($name);

		$newPath = $parentPath . '/' . $newName;
		$oldPath = $this->path;

		\OC\Files\Filesystem::rename($this->path, $newPath);

		$this->path = $newPath;

		$query = OC_DB::prepare( 'UPDATE `*PREFIX*properties` SET `propertypath` = ?'
			.' WHERE `userid` = ? AND `propertypath` = ?' );
		$query->execute( array( $newPath, OC_User::getUser(), $oldPath ));

	}

	public function setFileinfoCache($fileinfo_cache)
	{
		$this->fileinfo_cache = $fileinfo_cache;
	}

	/**
	 * @brief Ensure that the fileinfo cache is filled
	 * @note Uses OC_FileCache or a direct stat
	 */
	protected function getFileinfoCache() {
		if (!isset($this->fileinfo_cache)) {
			if ($fileinfo_cache = \OC\Files\Filesystem::getFileInfo($this->path)) {
			} else {
				$fileinfo_cache = \OC\Files\Filesystem::stat($this->path);
			}

			$this->fileinfo_cache = $fileinfo_cache;
		}
	}

	public function setPropertyCache($property_cache)
	{
		$this->property_cache = $property_cache;
	}

	/**
	 * @brief Returns the last modification time, as a unix timestamp
	 * @return int
	 */
	public function getLastModified() {
		$this->getFileinfoCache();
		return $this->fileinfo_cache['mtime'];

	}

	/**
	 *  sets the last modification time of the file (mtime) to the value given
	 *  in the second parameter or to now if the second param is empty.
	 *  Even if the modification time is set to a custom value the access time is set to now.
	 */
	public function touch($mtime) {
		\OC\Files\Filesystem::touch($this->path, $mtime);
	}

	/**
	 * @brief Updates properties on this node,
	 * @param array $mutations
	 * @see Sabre_DAV_IProperties::updateProperties
	 * @return bool|array
	 */
	public function updateProperties($properties) {
		$existing = $this->getProperties(array());
		foreach($properties as $propertyName => $propertyValue) {
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if(array_key_exists( $propertyName, $existing )) {
					$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*properties`'
						.' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?' );
					$query->execute( array( OC_User::getUser(), $this->path, $propertyName ));
				}
			}
			else {
				if( strcmp( $propertyName, self::GETETAG_PROPERTYNAME) === 0 ) {
					\OC\Files\Filesystem::putFileInfo($this->path, array('etag'=> $propertyValue));
				} elseif( strcmp( $propertyName, self::LASTMODIFIED_PROPERTYNAME) === 0 ) {
					$this->touch($propertyValue);
				} else {
					if(!array_key_exists( $propertyName, $existing )) {
						$query = OC_DB::prepare( 'INSERT INTO `*PREFIX*properties`'
							.' (`userid`,`propertypath`,`propertyname`,`propertyvalue`) VALUES(?,?,?,?)' );
						$query->execute( array( OC_User::getUser(), $this->path, $propertyName,$propertyValue ));
					} else {
						$query = OC_DB::prepare( 'UPDATE `*PREFIX*properties` SET `propertyvalue` = ?'
							.' WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?' );
						$query->execute( array( $propertyValue,OC_User::getUser(), $this->path, $propertyName ));
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
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*properties`'
		.' WHERE `userid` = ? AND `propertypath` = ?' );
		$query->execute( array( OC_User::getUser(), $this->path));

		$this->setPropertyCache(null);
	}

	/**
	 * @brief Returns a list of properties for this nodes.;
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
			$result = OC_DB::executeAudited( $sql, array( OC_User::getUser(), $this->path ) );

			$this->property_cache = array();
			while( $row = $result->fetchRow()) {
				$this->property_cache[$row['propertyname']] = $row['propertyvalue'];
			}

			// Don't call the static getETagPropertyForPath, its result is not cached
			$this->getFileinfoCache();
			if ($this->fileinfo_cache['etag']) {
				$this->property_cache[self::GETETAG_PROPERTYNAME] = '"'.$this->fileinfo_cache['etag'].'"';
			} else {
				$this->property_cache[self::GETETAG_PROPERTYNAME] = null;
			}
		}

		// if the array was empty, we need to return everything
		if(count($properties) == 0) {
			return $this->property_cache;
		}

		$props = array();
		foreach($properties as $property) {
			if (isset($this->property_cache[$property])) {
				$props[$property] = $this->property_cache[$property];
			}
		}

		return $props;
	}

	/**
	 * Returns the ETag surrounded by double-quotes for this path.
	 * @param string $path Path of the file
	 * @return string|null Returns null if the ETag can not effectively be determined
	 */
	protected function getETagPropertyForPath($path) {
		$data = $this->getFS()->getFileInfo($path);
		if (isset($data['etag'])) {
			return '"'.$data['etag'].'"';
		}
		return null;
	}

	protected function getFS() {
		if (is_null($this->fileView)) {
			$this->fileView = \OC\Files\Filesystem::getView();
		}
		return $this->fileView;
	}

	/**
	 * @return mixed
	 */
	public function getFileId()
	{
		$this->getFileinfoCache();

		if (isset($this->fileinfo_cache['fileid'])) {
			$instanceId = OC_Util::getInstanceId();
			$id = sprintf('%08d', $this->fileinfo_cache['fileid']);
			return $id . $instanceId;
		}

		return null;
	}
}
