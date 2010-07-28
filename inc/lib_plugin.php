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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

class OC_PLUGIN{
	static private $blacklist=array();
	
	/**
	* load the plugin with the given id
	* @param string id
	* @return bool
	*/
	static public function load($id){
		global $SERVERROOT;
		if(is_dir($SERVERROOT.'/plugins/'.$id) and is_file($SERVERROOT.'/plugins/'.$id.'/plugin.xml')){
			$plugin=new DOMDocument();
			$plugin->load($SERVERROOT.'/plugins/'.$id.'/plugin.xml');
			$pluginId=$plugin->getElementsByTagName('id')->item(0)->textContent;
			if($pluginId==$id){//sanity check for plugins installed in the wrong folder
				$childs=$plugin->documentElement->childNodes;
				foreach($childs as $child){
					if($child->nodeType==XML_ELEMENT_NODE and $child->tagName=='include'){
						$file=$SERVERROOT.'/plugins/'.$id.'/'.$child->textContent;
						include($file);
					}
				}
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Load all plugins that aren't blacklisted
	 */
	public static function loadPlugins() {
		global $SERVERROOT;
		$plugins = array();
		$blacklist=self::loadBlacklist();
		$fd = opendir($SERVERROOT . '/plugins');
		while ( false !== ($filename = readdir($fd)) ) {
			if ( $filename<>'.' AND $filename<>'..' AND ('.' != substr($filename, 0, 1)) AND array_search($filename,$blacklist)===false) {
				self::load($filename);
			}
		}
		closedir($fd);
	}
	
	/**
	* load the blacklist from blacklist.txt
	* @return array
	*/
	private static function loadBlacklist(){
		global $SERVERROOT;
		if(count(self::$blacklist)>0){
			return self::$blacklist;
		}
		$blacklist=array();
		if(is_file($SERVERROOT.'/plugins/blacklist.txt')){
			$file=file_get_contents($SERVERROOT.'/plugins/blacklist.txt');
			$lines=explode("\n",$file);
			foreach($lines as $line){
				$id=trim($line);
				if($id!='' and is_dir($SERVERROOT.'/plugins/'.$id)){
					$blacklist[]=$id;
				}
			}
		}
		self::$blacklist=$blacklist;
		return $blacklist;
	}
	
	/**
	* save a blacklist to blacklist.txt
	* @param array blacklist
	*/
	private static function saveBlacklist($blacklist){
		global $SERVERROOT;
		$file='';
		foreach($blacklist as $item){
			$file.="$item\n";
		}
		self::$blacklist=$blacklist;
		file_put_contents($SERVERROOT.'/plugins/blacklist.txt',$file);
	}
	
	/**
	* add a plugin to the blacklist
	* @param string id
	*/
	public static function addToBlacklist($id){
		$blacklist=self::loadBlacklist();
		if(array_search($id,$blacklist)===false){
			$blacklist[]=$id;
			self::$blacklist=$blacklist;
			self::saveBlacklist($blacklist);
		}
	}
	
	/**
	* remove a plugin to the blacklist
	* @param string id
	*/
	public static function removeFromBlacklist($id){
		$blacklist=self::loadBlacklist();
		$index=array_search($id,$blacklist);
		if($index!==false){
			unset($blacklist[$index]);
			self::$blacklist=$blacklist;
			self::saveBlacklist($blacklist);
		}
	}
}

?>
