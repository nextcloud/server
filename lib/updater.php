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
 * Class that handels autoupdating of ownCloud
 */
class OC_Updater{

	/**
	 * Check if a new version is available
	 */
	public static function check(){
		OC_Appconfig::setValue('core', 'lastupdatedat',microtime(true));
		if(OC_Appconfig::getValue('core', 'installedat','')=='') OC_Appconfig::setValue('core', 'installedat',microtime(true));

		$updaterurl='http://apps.owncloud.com/updater.php';
		$version=OC_Util::getVersion();
		$version['installed']=OC_Appconfig::getValue('core', 'installedat');
		$version['updated']=OC_Appconfig::getValue('core', 'lastupdatedat');
		$version['updatechannel']='stable';
		$version['edition']=OC_Util::getEditionString();
		$versionstring=implode('x',$version);

		//fetch xml data from updater
		$url=$updaterurl.'?version='.$versionstring;
                $xml=@file_get_contents($url);
                if($xml==FALSE){
                        return array();
                }
                $data=@simplexml_load_string($xml);

		$tmp=array();
                $tmp['version'] = $data->version;
                $tmp['versionstring'] = $data->versionstring;
                $tmp['url'] = $data->url;
                $tmp['web'] = $data->web;

                return $tmp;
	}

	public static function ShowUpdatingHint(){
		if(OC_Config::getValue('updatechecker', true)==true){
			$data=OC_Updater::check();
			if(isset($data['version']) and $data['version']<>'') {
				$txt='<span style="color:#AA0000; font-weight:bold;">'.$data['versionstring'].' is available. Get <a href="'.$data['web'].'">more information</a></span>';
			}else{
				$txt='up to date';
			}
		}else{
			$txt='updates check is disabled';
		}
		return($txt);
	}


	/**
	 * do ownCloud update
	 */
	public static function doUpdate(){

		//update ownCloud core

		//update all apps

		//update version in config

	}
}
?>
