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
 * This class models a feed.
 */
class OC_News_Feed extends OC_News_Collection {

	private $url;
	private $spfeed; //encapsulate a SimplePie_Core object
	private $items;  //array that contains all the items of the feed

	public function __construct($url, $title, $items, $id = null){
		$this->url = $url;
		$this->title = $title;
		$this->items = $items;
		if ($id !== null){
			parent::__construct($id);
		}
	}
	
	public function getUrl(){
		return $this->url;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setItems($items){
		$this->items = $items;
	}

	public function getItems(){
		return $this->items;
	}
}
