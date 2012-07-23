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

	/**
	 * The path to the current node
	 *
	 * @var string
	 */
	protected $path;
	/**
	 * file stat cache
	 * @var array
	 */
	protected $stat_cache;

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
		
		$query = OC_DB::prepare( 'UPDATE *PREFIX*properties SET propertypath = ? WHERE userid = ? AND propertypath = ?' );
		$query->execute( array( $newPath,OC_User::getUser(), $oldPath ));

	}

	/**
	 * Set the stat cache
	 */
	protected function stat() {
		if (!isset($this->stat_cache)) {
			$this->stat_cache = OC_Filesystem::stat($this->path);
		}
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	public function getLastModified() {
		$this->stat();
		return $this->stat_cache['mtime'];

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
		$existing = $this->getProperties(array());
		foreach($properties as $propertyName => $propertyValue) {
			$propertyName = preg_replace("/^{.*}/", "", $propertyName); // remove leading namespace from property name
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if(array_key_exists( $propertyName, $existing )){
					$query = OC_DB::prepare( 'DELETE FROM *PREFIX*properties WHERE userid = ? AND propertypath = ? AND propertyname = ?' );
					$query->execute( array( OC_User::getUser(), $this->path, $propertyName ));
				}
			}
			else {
				if( strcmp( $propertyName, "lastmodified") === 0) {
					$this->touch($propertyValue);
				} else {
					if(!array_key_exists( $propertyName, $existing )){
						$query = OC_DB::prepare( 'INSERT INTO *PREFIX*properties (userid,propertypath,propertyname,propertyvalue) VALUES(?,?,?,?)' );
						$query->execute( array( OC_User::getUser(), $this->path, $propertyName,$propertyValue ));
					} else {
						$query = OC_DB::prepare( 'UPDATE *PREFIX*properties SET propertyvalue = ? WHERE userid = ? AND propertypath = ? AND propertyname = ?' );
						$query->execute( array( $propertyValue,OC_User::getUser(), $this->path, $propertyName ));
					}
				}
			}

		}
		return true;
	}

	/**
	 * Returns a list of properties for this nodes.;
	 *
	 * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
	 * If the array is empty, all properties should be returned
	 *
	 * @param array $properties
	 * @return void
	 */
	function getProperties($properties) {
		// At least some magic in here :-)
		$query = OC_DB::prepare( 'SELECT * FROM *PREFIX*properties WHERE userid = ? AND propertypath = ?' );
		$result = $query->execute( array( OC_User::getUser(), $this->path ));

		$existing = array();
		while( $row = $result->fetchRow()){
			$existing[$row['propertyname']] = $row['propertyvalue'];
		}

		if(count($properties) == 0){
			return $existing;
		}
		
		// if the array was empty, we need to return everything
		$props = array();
		foreach($properties as $property) {
			if (isset($existing[$property])) $props[$property] = $existing[$property];
		}
		return $props;
	}
}
