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
	
		$xml=file_get_contents($url);
		$data=simplexml_load_string($xml);
	
		$tmp=$data->data->category;
		$cats=array();
		for($i = 0; $i < count($tmp); $i++) {
			$cats[$i]=$tmp[$i]->name;
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
                if(is_array($categories)) {
			$categoriesstring=implode('x',$categories);
		}else{
			$categoriesstring=$categories;
		}
		$url='http://api.opendesktop.org/v1/content/data?categories='.urlencode($categoriesstring).'&sortmode=new&page=0&pagesize=10';
	
		$apps=array();
		$xml=file_get_contents($url);
		$data=simplexml_load_string($xml);
	
		$tmp=$data->data->content;
		for($i = 0; $i < count($tmp); $i++) {
			$app=array();
			$app['id']=$tmp[$i]->id;
			$app['name']=$tmp[$i]->name;
			$app['type']=$tmp[$i]->typeid;
			$app['typename']=$tmp[$i]->typename;
			$app['personid']=$tmp[$i]->personid;
			$app['detailpage']=$tmp[$i]->detailpage;
			$app['preview']=$tmp[$i]->smallpreviewpic1;
			$app['changed']=strtotime($tmp[$i]->changed);
			$app['description']=$tmp[$i]->description;
	
			$apps[]=$app;
		} 
		return $apps;
	}


        /**
         * @brief Get an the applications from the OCS server
         * @returns array with application data
         *
         * This function returns an  applications from the OCS server
         */
        public static function getApplication($id){
                $url='http://api.opendesktop.org/v1/content/data/'.urlencode($id);

                $xml=file_get_contents($url);
                $data=simplexml_load_string($xml);

                $tmp=$data->data->content;
                $app=array();
                $app['id']=$tmp->id;
                $app['name']=$tmp->name;
                $app['type']=$tmp->typeid;
                $app['typename']=$tmp->typename;
                $app['personid']=$tmp->personid;
                $app['detailpage']=$tmp->detailpage;
                $app['preview1']=$tmp->smallpreviewpic1;
                $app['preview2']=$tmp->smallpreviewpic2;
                $app['preview3']=$tmp->smallpreviewpic3;
                $app['changed']=strtotime($tmp->changed);
                $app['description']=$tmp->description;

                return $app;
        }




}
?>
