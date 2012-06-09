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

class OC_OCSClient{

	/**
	 * @brief Get the url of the OCS AppStore server. 
	 * @returns string of the AppStore server
	 *
	 * This function returns the url of the OCS AppStore server. It´s possible to set it in the config file or it will fallback to the default
	 */
	private static function getAppStoreURL(){
		$configurl=OC_Config::getValue('appstoreurl', '');
		if($configurl<>'') {
			$url=$configurl;
		}else{
			$url='http://api.apps.owncloud.com/v1';
		}
		return($url);
	}

        /**
         * @brief Get the url of the OCS KB server. 
         * @returns string of the KB server
         * This function returns the url of the OCS knowledge base server. It´s possible to set it in the config file or it will fallback to the default
         */
        private static function getKBURL(){
                $configurl=OC_Config::getValue('knowledgebaseurl', '');
                if($configurl<>'') {
                        $url=$configurl;
                }else{
                        $url='http://api.apps.owncloud.com/v1';
                }
                return($url);
        }


	/**
	 * @brief Get all the categories from the OCS server
	 * @returns array with category ids
	 *
	 * This function returns a list of all the application categories on the OCS server
	 */
	public static function getCategories(){
		$url=OC_OCSClient::getAppStoreURL().'/content/categories';
	
		$xml=@file_get_contents($url);
		if($xml==FALSE){
			return NULL;
		}
		$data=simplexml_load_string($xml);
	
		$tmp=$data->data;
		$cats=array();

		foreach($tmp->category as $key=>$value) {

			$id= (int) $value->id;
			$name= (string) $value->name;
			$cats[$id]=$name;

		}

		return $cats;
	}

	/**
	 * @brief Get all the applications from the OCS server
	 * @returns array with application data
	 *
	 * This function returns a list of all the applications on the OCS server
	 */
	public static function getApplications($categories,$page){
		if(OC_Config::getValue('appstoreenabled', true)==false){
			return(array());
		}

		if(is_array($categories)) {
			$categoriesstring=implode('x',$categories);
		}else{
			$categoriesstring=$categories;
		}
		$url=OC_OCSClient::getAppStoreURL().'/content/data?categories='.urlencode($categoriesstring).'&sortmode=new&page='.urlencode($page).'&pagesize=100';
		$apps=array();
		$xml=@file_get_contents($url);
		if($xml==FALSE){
			return NULL;
		}
		$data=simplexml_load_string($xml);

		$tmp=$data->data->content;
		for($i = 0; $i < count($tmp); $i++) {
			$app=array();
			$app['id']=(string)$tmp[$i]->id;
			$app['name']=(string)$tmp[$i]->name;
			$app['type']=(string)$tmp[$i]->typeid;
			$app['typename']=(string)$tmp[$i]->typename;
			$app['personid']=(string)$tmp[$i]->personid;
			$app['license']=(string)$tmp[$i]->license;
			$app['detailpage']=(string)$tmp[$i]->detailpage;
			$app['preview']=(string)$tmp[$i]->smallpreviewpic1;
			$app['changed']=strtotime($tmp[$i]->changed);
			$app['description']=(string)$tmp[$i]->description;
	
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
		$url=OC_OCSClient::getAppStoreURL().'/content/data/'.urlencode($id);

		$xml=@file_get_contents($url);
		if($xml==FALSE){
			OC_Log::write('core','Unable to parse OCS content',OC_Log::FATAL);
			return NULL;
		}
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
		$app['detailpage']=$tmp->detailpage;

		return $app;
	}

        /**
         * @brief Get the download url for an application from the OCS server
         * @returns array with application data
         *
         * This function returns an download url for an applications from the OCS server
         */
        public static function getApplicationDownload($id,$item){
                $url=OC_OCSClient::getAppStoreURL().'/content/download/'.urlencode($id).'/'.urlencode($item);

                $xml=@file_get_contents($url);
                if($xml==FALSE){
                        OC_Log::write('core','Unable to parse OCS content',OC_Log::FATAL);
                        return NULL;
                }
                $data=simplexml_load_string($xml);

                $tmp=$data->data->content;
                $app=array();
                if(isset($tmp->downloadlink)) { 
	 		$app['downloadlink']=$tmp->downloadlink;
		}else{
	 		$app['downloadlink']='';
		}
                return $app;
        }


	/**
	 * @brief Get all the knowledgebase entries from the OCS server
	 * @returns array with q and a data
	 *
	 * This function returns a list of all the knowledgebase entries from the OCS server
	 */
	public static function getKnownledgebaseEntries($page,$pagesize,$search=''){	
		if(OC_Config::getValue('knowledgebaseenabled', true)==false){
			$kbe=array();
			$kbe['totalitems']=0;
			return $kbe;
		}

		$p= (int) $page;
		$s= (int) $pagesize;
		if($search<>'') $searchcmd='&search='.urlencode($search); else $searchcmd='';
		$url=OC_OCSClient::getKBURL().'/knowledgebase/data?type=150&page='.$p.'&pagesize='.$s.$searchcmd;

		$kbe=array();
		$xml=@file_get_contents($url);
		if($xml==FALSE){
			OC_Log::write('core','Unable to parse knowledgebase content',OC_Log::FATAL);
			return NULL;
		}
		$data=simplexml_load_string($xml);

		$tmp=$data->data->content;
		for($i = 0; $i < count($tmp); $i++) {
			$kb=array();
			$kb['id']=$tmp[$i]->id;
			$kb['name']=$tmp[$i]->name;
			$kb['description']=$tmp[$i]->description;
			$kb['answer']=$tmp[$i]->answer;
			$kb['preview1']=$tmp[$i]->smallpreviewpic1;
			$kb['detailpage']=$tmp[$i]->detailpage;
			$kbe[]=$kb;
		}
		$total=$data->meta->totalitems;
		$kbe['totalitems']=$total;
                return $kbe;
	}



}
