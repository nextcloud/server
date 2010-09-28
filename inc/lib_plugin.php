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
		$data=self::getPluginData($id);
		if($data){
			if(isset($data['info']['require'])){
				$minVersion=explode('.',$data['info']['require']);
				$version=OC_UTIL::getVersion();
				$roundTo=count($minVersion);
				while(count($version)>$roundTo){
					if($version[count($version)-1]>=50){
						$version[count($version)-2]++;
					}
					unset($version[count($version)-1]);
				}
				for($i=0;$i<count($minVersion);$i++){
					if($version[$i]<$minVersion[$i]){
						return false;
					}
				}
			}
			foreach($data['runtime'] as $include){
				include($SERVERROOT.'/plugins/'.$id.'/'.$include);
			}
		}
		return false;
	}
	
	/**
	 * Get a list of all installed plugins
	 */
	public static function listPlugins() {
		global $SERVERROOT;
		$plugins = array();
		$fd = opendir($SERVERROOT . '/plugins');
		while ( false !== ($filename = readdir($fd)) ) {
			if ( $filename<>'.' AND $filename<>'..' AND ('.' != substr($filename, 0, 1))) {
				if(file_exists($SERVERROOT . '/plugins/'.$filename.'/plugin.xml')){
					$plugins[]=$filename;
				}
			}
		}
		closedir($fd);
		return $plugins;
	}
	
	/**
	 * Load all plugins that aren't blacklisted
	 */
	public static function loadPlugins() {
		global $SERVERROOT;
		$plugins = self::listPlugins();
		$blacklist=self::loadBlacklist();
		$fd = opendir($SERVERROOT . '/plugins');
		foreach($plugins as $plugin){
			if (array_search($plugin,$blacklist)===false) {
				self::load($plugin);
			}
		}
		closedir($fd);
	}
	
	/**
	* load the blacklist from blacklist.txt
	* @return array
	*/
	public static function loadBlacklist(){
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
	
	/**
	( Load data from the plugin.xml of a plugin, either identified by the plugin or the path of the plugin.xml file
	* @param string id
	*( @return array
	*/
	public static function getPluginData($id){
		global $SERVERROOT;
		if(is_file($id)){
			$file=$id;
		}
		if(!is_dir($SERVERROOT.'/plugins/'.$id) or !is_file($SERVERROOT.'/plugins/'.$id.'/plugin.xml')){
			return false;
		}else{
			$file=$SERVERROOT.'/plugins/'.$id.'/plugin.xml';
		}
		$data=array();
		$plugin=new DOMDocument();
		$plugin->load($file);
		$data['version']=$plugin->documentElement->getAttribute('version');
		$info=$plugin->getElementsByTagName('info');
		if($info->length>0){
			$info=$info->item(0);
			$data['info']=array();
			foreach($info->childNodes as $child){
				if($child->nodeType==XML_ELEMENT_NODE){
					$data['info'][$child->tagName]=$child->textContent;
				}
			}
		}
		$runtime=$plugin->getElementsByTagName('runtime');
		if($runtime->length>0){
			$runtime=$runtime->item(0);
			$data['runtime']=array();
			foreach($runtime->childNodes as $child){
				if($child->nodeType==XML_ELEMENT_NODE and $child->tagName=='include'){
					$data['runtime'][]=$child->textContent;
				}
			}
		}
		$install=$plugin->getElementsByTagName('install');
		if($install->length>0){
			$install=$install->item(0);
			$data['install']=array();
			foreach($install->childNodes as $child){
				if($child->nodeType==XML_ELEMENT_NODE){
					$data['install']['include']=array();
					$data['install']['dialog']=array();
					$data['install']['database']=array();
					switch($child->tagName){
						case 'include':
							$data['install']['include'][]=$child->textContent;
							break;
						case 'dialog':
							$data['install']['dialog'][]=$child->textContent;
							break;
						case 'database':
							$data['install']['database'][]=$child->textContent;
							break;
					}
				}
			}
		}
		$uninstall=$plugin->getElementsByTagName('uninstall');
		if($uninstall->length>0){
			$uninstall=$uninstall->item(0);
			$data['uninstall']=array();
			foreach($uninstall->childNodes as $child){
				if($child->nodeType==XML_ELEMENT_NODE){
					$data['uninstall']['include']=array();
					$data['uninstall']['dialog']=array();
					switch($child->tagName){
						case 'include':
							$data['uninstall']['include'][]=$child->textContent;
							break;
						case 'dialog':
							$data['uninstall']['dialog'][]=$child->textContent;
							break;
					}
				}
			}
		}
		return $data;
	}
}

?>
