<?php
/**
 * Base node-class 
 *
 * The node class implements the method used by both the File and the Directory classes 
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE IF NOT EXISTS `properties` (
 *   `userid` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
 *   `propertypath` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 *   `propertyname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 *   `propertyvalue` text COLLATE utf8_unicode_ci NOT NULL
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	public function getLastModified() {

		return OC_Filesystem::filemtime($this->path);

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
			// If it was null, we need to delete the property
			if (is_null($propertyValue)) {
				if(array_key_exists( $propertyName, $existing )){
					$query = OC_DB::prepare( 'DELETE FROM *PREFIX*properties WHERE userid = ? AND propertypath = ? AND propertyname = ?' );
					$query->execute( array( OC_User::getUser(), $this->path, $propertyName ));
				}
			}
			else {
				if( strcmp( $propertyName, "lastmodified")) {
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
