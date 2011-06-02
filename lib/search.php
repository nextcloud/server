<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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


/**
 * provides an interface to all search providers
 */
class OC_SEARCH{
	static private $providers=array();
	
	/**
	 * register a new search provider to be used
	 * @param OC_SearchProvider $provider
	 */
	public static function registerProvider($provider){
		self::$providers[]=$provider;
	}
	
	/**
	 * search all provider for $query
	 * @param string query
	 * @return array An array of OC_SearchResult's
	 */
	public static function search($query){
		$results=array();
		foreach(self::$providers as $provider){
			$results=array_merge($results,$provider->search($query));
		}
		return $results;
	}
}

/**
 * provides search functionalty
 */
abstract class OC_SearchProvider{
	public function __construct(){
		OC_SEARCH::registerProvider($this);
	}
	
	/**
	 * search for $query
	 * @param string $query
	 * @return array An array of OC_SearchResult's
	 */
	abstract function search($query);
}

/**
 * a result of a search
 */
class OC_SearchResult{
	private $name;
	private $text;
	private $link;
	private $type;
	
	/**
	 * create a new search result
	 * @param string $name short name for the result
	 * @param string $text some more information about the result
	 * @param string $link link for the result
	 * @param string $type the type of result as human readable string ('File', 'Music', etc)
	 */
	public function __construct($name,$text,$link,$type){
		$this->name=$name;
		$this->text=$text;
		$this->link=$link;
		$this->type=$type;
	}

	public function __get($name){
		switch($name){
			case 'name':
				return $this->name;
			case 'text':
				return $this->text;
			case 'link':
				return $this->link;
			case 'type':
				return $this->type;
		}
	}
}

class OC_FileSearchProvider extends OC_SearchProvider{
	function search($query){
		$files=OC_FILESYSTEM::search($query);
		$results=array();
		foreach($files as $file){
			if(OC_FILESYSTEM::is_dir($file)){
				$results[]=new OC_SearchResult(basename($file),$file,OC_HELPER::linkTo( 'files', 'index.php?dir='.$file ),'Files');
			}else{
				$results[]=new OC_SearchResult(basename($file),$file,OC_HELPER::linkTo( 'files', 'download.php?file='.$file ),'Files');
			}
		}
		return $results;
	}
}

new OC_FileSearchProvider();
?>