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
 * This class models a folder that contains feeds.
 */
class OC_News_Folder extends OC_News_Collection {

	private $name;
	private $children;
	private $parent;

	public function __construct($name, $id = null, OC_News_Collection $parent = null){
		$this->name = $name;
		if ($id !== null){
			parent::__construct($id);
		}
		$this->children = array();
		if ($parent !== null){
			$this->parent = $parent;
		}
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getParentId(){
		if ($this->parent === null){
			return 0;
		}
		return $this->parent->getId();
	}

	public function addChild(OC_News_Collection $child){
		$this->children[] = $child;
	}

	public function getChildren(){
		return $this->children;
	}

}