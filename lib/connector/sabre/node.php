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
	 * Sets up the node, expects a full path name
	 *
	 * @param string $path
	 * @return void
	 */
	public function __construct($path) {
		$this->path = $path;
	}



	/**
	 * Returns the name of the node
	 *
	 * @return string
	 */
	public function getName() {

		list(, $name)  = Sabre_DAV_URLUtil::splitPath($this->path);
		return $name;

	}

	/**
	 * Renames the node
	 *
	 * @param string $name The new name
	 * @return void
	 */
	public function setName($name) {

		list($parentPath, ) = Sabre_DAV_URLUtil::splitPath($this->path);
		list(, $newName) = Sabre_DAV_URLUtil::splitPath($name);

		$newPath = $parentPath . '/' . $newName;
		$oldPath = $this->path;

		OC_Filesystem::rename($this->path,$newPath);

		$this->path = $newPath;

		$query = OC_DB::prepare( 'UPDATE `*PREFIX*properties` SET `propertypath` = ? WHERE `userid` = ? AND `propertypath` = ?' );
		$query->execute( array( $newPath,OC_User::getUser(), $oldPath ));

	}

	public function setFileinfoCache($fileinfo_cache)
	{
		$this->fileinfo_cache = $fileinfo_cache;
	}

	/**
	 * Make sure the fileinfo cache is filled. Uses OC_FileCache or a direct stat
	 */
	protected function getFileinfoCache() {
		if (!isset($this->fileinfo_cache)) {
			if ($fileinfo_cache = OC_FileCache::get($this->path)) {
			} else {
				$fileinfo_cache = OC_Filesystem::stat($this->path);
			}

			$this->fileinfo_cache = $fileinfo_cache;
		}
	}

	public function setPropertyCache($property_cache)
	{
		$this->property_cache = $property_cache;
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
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
		OC_Filesystem::touch($this->path, $mtime);
	}

	/**
	 * Updates properties on this node,
	 *
	 * @param array $mutations
	 * @see Sabre_DAV_IProperties::updateProperties
	 * @return bool|array
	 */
	public function updateProperties($properties) {
		// get source path of shared files
		$source = self::getFileSource($this->path);

		$existing = $this->getProperties(array());
		foreach($properties as $propertyName => $propertyValue) {
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if(array_key_exists( $propertyName, $existing )) {
					$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?' );
					$query->execute( array( $source['user'], $source['path'], $propertyName ));
				}
			}
			else {
				if( strcmp( $propertyName, self::LASTMODIFIED_PROPERTYNAME) === 0 ) {
					$this->touch($propertyValue);
				} else {
					if(!array_key_exists( $propertyName, $existing )) {
						$query = OC_DB::prepare( 'INSERT INTO `*PREFIX*properties` (`userid`,`propertypath`,`propertyname`,`propertyvalue`) VALUES(?,?,?,?)' );
						$query->execute( array( $source['user'], $source['path'], $propertyName, $propertyValue ));
					} else {
						$query = OC_DB::prepare( 'UPDATE `*PREFIX*properties` SET `propertyvalue` = ? WHERE `userid` = ? AND `propertypath` = ? AND `propertyname` = ?' );
						$query->execute( array( $propertyValue, $source['user'], $source['path'], $propertyName ));
					}
				}
			}

		}
		$this->setPropertyCache(null);
		return true;
	}

	/**
	 * Returns a list of properties for this nodes.;
	 *
	 * The properties list is a list of propertynames the client requested,
	 * encoded as xmlnamespace#tagName, for example:
	 * http://www.example.org/namespace#author
	 * If the array is empty, all properties should be returned
	 *
	 * @param array $properties
	 * @return array
	 */
	public function getProperties($properties) {
		
		$source = self::getFileSource($this->path);
		
		if (is_null($this->property_cache) || empty($this->property_cache)) {
			$query = OC_DB::prepare( 'SELECT * FROM `*PREFIX*properties` WHERE `userid` = ? AND `propertypath` = ?' );
			$result = $query->execute( array( $source['user'], $source['path'] ));

			$this->property_cache = array();
			while( $row = $result->fetchRow()) {
				$this->property_cache[$row['propertyname']] = $row['propertyvalue'];
			}
		}
		
		// if the array was empty, we need to return everything
		if(count($properties) == 0) {
			return $this->property_cache;
		}

		$props = array();
		foreach($properties as $property) {
			if (isset($this->property_cache[$property])) $props[$property] = $this->property_cache[$property];
		}
		return $props;
	}

	/**
	 * Creates a ETag for this path.
	 * @param string $path Path of the file
	 * @return string|null Returns null if the ETag can not effectively be determined
	 */
	static protected function createETag($path) {
		if(self::$ETagFunction) {
			$hash = call_user_func(self::$ETagFunction, $path);
			return $hash;
		}else{
			return uniqid('', true);
		}
	}

	/**
	 * Returns the ETag surrounded by double-quotes for this path.
	 * @param string $path Path of the file
	 * @return string|null Returns null if the ETag can not effectively be determined
	 */
	static public function getETagPropertyForPath($path) {
		$tag = self::createETag($path);
		if (empty($tag)) {
			return null;
		}
		
		$source = self::getFileSource($path);
			
		$etag = '"'.$tag.'"';
		$query = OC_DB::prepare( 'INSERT INTO `*PREFIX*properties` (`userid`,`propertypath`,`propertyname`,`propertyvalue`) VALUES(?,?,?,?)' );
		$query->execute( array( $source['user'], $source['path'], self::GETETAG_PROPERTYNAME, $etag ));
		return $etag;
	}

	/**
	 * Remove the ETag from the cache.
	 * @param string $path Path of the file
	 */
	static public function removeETagPropertyForPath($path) {
		// remove tags from this and parent paths
		$source = self::getFileSource($path);
		$path = $source['path'];
		
		$paths = array();
		while ($path != '/' && $path != '.' && $path != '' && $path != '\\') {
			$paths[] = $path;
			$path = dirname($path);
		}
		if (empty($paths)) {
			return;
		}
		$paths[] = $path;
		$path_placeholders = join(',', array_fill(0, count($paths), '?'));
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*properties`'
			.' WHERE `userid` = ?'
			.' AND `propertyname` = ?'
			.' AND `propertypath` IN ('.$path_placeholders.')'
			);
		$vals = array( $source['user'], self::GETETAG_PROPERTYNAME );
		$query->execute(array_merge( $vals, $paths ));
		
		//remove etag for all Shared folders
		$query = OC_DB::prepare( 'DELETE FROM `*PREFIX*properties`'
				.' WHERE `propertypath` = \'/Shared\' '
		);
		$query->execute(array());
		
	}
	
	protected static function getFileSource($path) {
		if (!strncmp($path, '/Shared/', 8) && OC_App::isEnabled('files_sharing')) {
			$source = OC_Files_Sharing_Util::getSourcePath(str_replace('/Shared/', '', $path));
			$parts = explode('/', $source, 4);
			$user =  $parts[1];
			$path = '/'.$parts[3];
		} else {
			$user = OC_User::getUser();
		}
		return(array('user' => $user, 'path' => $path));
	}
}
