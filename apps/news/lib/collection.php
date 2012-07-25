<?php
/**
* ownCloud - News app
*
* @author Alessandro Cosentino
* Copyright (c) 2012 - Alessandro Cosentino <cosenal@gmail.com>
* 
* This file is licensed under the Affero General Public License version 3 or later.
* See the COPYING-README file
* 
*/

/**
 * This class models a collection, which is either a feed or a folder.
 */
class OC_News_Collection {
	
	private $id;
	
	public function __construct($id){
		$this->id = $id;
	}

	public function getId(){
		return $this->id;
	}
	
	public function setId($id){
		$this->id = $id;
	}
	
}