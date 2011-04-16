<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
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
 * This class provides an easy way for apps to store config values in the
 * database.
 */

class OC_OCSCLIENT{

	/**
	 * @brief Get all the categories from the OCS server
	 * @returns array with category ids
	 *
	 * This function returns a list of all the application categories on the OCS server
	 */
	public static function getCategories(){
		$url='http://api.opendesktop.org/v1/content/categories';
	
		$cats=array();
		$xml=file_get_contents($url);
		$data=simplexml_load_string($xml);
	
		$tmp=$data->data->category;
		for($i = 0; $i < count($tmp); $i++) {
			$cat=array();
			$cat['id']=$tmp[$i]->id;
			$cat['name']=$tmp[$i]->name;
			$cats[]=$cat;
		}
		return $cats;
	}

	/**
	 * @brief Get all the applications from the OCS server
	 * @returns array with application data
	 *
	 * This function returns a list of all the applications on the OCS server
	 */
	public static function getApplications($categories){
		$categoriesstring=implode('x',$categories);
		$url='http://api.opendesktop.org/v1/content/data?categories='.$ocscategories['ids'].'&sortmode=new&page=0&pagesize=10';
	
		$apps=array();
		$xml=file_get_contents($url);
		$data=simplexml_load_string($xml);
	
		$tmp=$data->data->content;
		for($i = 0; $i < count($tmp); $i++) {
			$app=array();
			$app['id']=$tmp[$i]->id;
			$app['name']=$tmp[$i]->name;
			$app['type']=$tmp[$i]->type;
			$app['personid']=$tmp[$i]->personid;
			$app['detailpage']=$tmp[$i]->detailpage;
			$app['preview']=$tmp[$i]->smallpreviewpic1;
			$app['changed']=$tmp[$i]->changed;
			$app['description']=$tmp[$i]->description;
	
			$apps[]=$app;
		} 
		return $apps;
	}

}
?>
