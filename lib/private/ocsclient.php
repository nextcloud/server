<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
	 * Returns whether the AppStore is enabled (i.e. because the AppStore is disabled for EE)
	 * @return bool
	 */
	protected static function isAppstoreEnabled() {
		if(OC::$server->getConfig()->getSystemValue('appstoreenabled', true) === false OR OC_Util::getEditionString() !== '') {
			return false;
		}

		return true;
	}

	/**
	 * Get the url of the OCS AppStore server.
	 * @return string of the AppStore server
	 *
	 * This function returns the url of the OCS AppStore server. It´s possible
	 * to set it in the config file or it will fallback to the default
	 */
	private static function getAppStoreURL() {
		return OC::$server->getConfig()->getSystemValue('appstoreurl', 'https://api.owncloud.com/v1');
	}

	/**
	 * Get the content of an OCS url call.
	 * @return string of the response
	 * This function calls an OCS server and returns the response. It also sets a sane timeout
	* @param string $url
	*/
	private static function getOCSresponse($url) {
		$data = \OC_Util::getUrlContent($url);
		return($data);
	}

	/**
	 * Get all the categories from the OCS server
	 * @return array|null an array of category ids or null
	 * @note returns NULL if config value appstoreenabled is set to false
	 * This function returns a list of all the application categories on the OCS server
	 */
	public static function getCategories() {
		if(!self::isAppstoreEnabled()) {
			return null;
		}
		$url=OC_OCSClient::getAppStoreURL().'/content/categories';
		$xml=OC_OCSClient::getOCSresponse($url);
		if($xml==false) {
			return null;
		}
		$loadEntities = libxml_disable_entity_loader(true);
		$data =	simplexml_load_string($xml);
		libxml_disable_entity_loader($loadEntities);

		$tmp=$data->data;
		$cats=array();

		foreach($tmp->category as $value) {

			$id= (int) $value->id;
			$name= (string) $value->name;
			$cats[$id]=$name;

		}

		return $cats;
	}

	/**
	 * Get all the applications from the OCS server
	 * @return array|null an array of application data or null
	 *
	 * This function returns a list of all the applications on the OCS server
	 * @param array|string $categories
	 * @param int $page
	 * @param string $filter
	 */
	public static function getApplications($categories, $page, $filter) {
		if(!self::isAppstoreEnabled()) {
			return(array());
		}

		if(is_array($categories)) {
			$categoriesstring=implode('x', $categories);
		}else{
			$categoriesstring=$categories;
		}

		$version='&version='.implode('x', \OC_Util::getVersion());
		$filterurl='&filter='.urlencode($filter);
		$url=OC_OCSClient::getAppStoreURL().'/content/data?categories='.urlencode($categoriesstring)
			.'&sortmode=new&page='.urlencode($page).'&pagesize=100'.$filterurl.$version;
		$apps=array();
		$xml=OC_OCSClient::getOCSresponse($url);

		if($xml==false) {
			return null;
		}
		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($xml);
		libxml_disable_entity_loader($loadEntities);

		$tmp = $data->data->content;
		$tmpCount = count($tmp);
		for($i = 0; $i < $tmpCount; $i++) {
			$app=array();
			$app['id']=(string)$tmp[$i]->id;
			$app['name']=(string)$tmp[$i]->name;
			$app['label']=(string)$tmp[$i]->label;
			$app['version']=(string)$tmp[$i]->version;
			$app['type']=(string)$tmp[$i]->typeid;
			$app['typename']=(string)$tmp[$i]->typename;
			$app['personid']=(string)$tmp[$i]->personid;
			$app['license']=(string)$tmp[$i]->license;
			$app['detailpage']=(string)$tmp[$i]->detailpage;
			$app['preview']=(string)$tmp[$i]->smallpreviewpic1;
			$app['preview-full']=(string)$tmp[$i]->previewpic1;
			$app['changed']=strtotime($tmp[$i]->changed);
			$app['description']=(string)$tmp[$i]->description;
			$app['score']=(string)$tmp[$i]->score;
			$app['downloads'] = (int)$tmp[$i]->downloads;

			$apps[]=$app;
		}
		return $apps;
	}


	/**
	 * Get an the applications from the OCS server
	 * @param string $id
	 * @return array|null an array of application data or null
	 *
	 * This function returns an applications from the OCS server
	 */
	public static function getApplication($id) {
		if(!self::isAppstoreEnabled()) {
			return null;
		}
		$url=OC_OCSClient::getAppStoreURL().'/content/data/'.urlencode($id);
		$xml=OC_OCSClient::getOCSresponse($url);

		if($xml==false) {
			OC_Log::write('core', 'Unable to parse OCS content for app ' . $id, OC_Log::FATAL);
			return null;
		}
		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($xml);
		libxml_disable_entity_loader($loadEntities);

		$tmp=$data->data->content;
		if (is_null($tmp)) {
			OC_Log::write('core', 'Invalid OCS content returned for app ' . $id, OC_Log::FATAL);
			return null;
		}
		$app=array();
		$app['id']=$tmp->id;
		$app['name']=$tmp->name;
		$app['version']=$tmp->version;
		$app['type']=$tmp->typeid;
		$app['label']=$tmp->label;
		$app['typename']=$tmp->typename;
		$app['personid']=$tmp->personid;
		$app['detailpage']=$tmp->detailpage;
		$app['preview1']=$tmp->smallpreviewpic1;
		$app['preview2']=$tmp->smallpreviewpic2;
		$app['preview3']=$tmp->smallpreviewpic3;
		$app['changed']=strtotime($tmp->changed);
		$app['description']=$tmp->description;
		$app['detailpage']=$tmp->detailpage;
		$app['score']=$tmp->score;

		return $app;
	}

	/**
	 * Get the download url for an application from the OCS server
	 * @return array|null an array of application data or null
	 *
	 * This function returns an download url for an applications from the OCS server
	 * @param string $id
	 * @param integer $item
	 */
	public static function getApplicationDownload($id, $item) {
		if(!self::isAppstoreEnabled()) {
			return null;
		}
		$url=OC_OCSClient::getAppStoreURL().'/content/download/'.urlencode($id).'/'.urlencode($item);
		$xml=OC_OCSClient::getOCSresponse($url);

		if($xml==false) {
			OC_Log::write('core', 'Unable to parse OCS content', OC_Log::FATAL);
			return null;
		}
		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($xml);
		libxml_disable_entity_loader($loadEntities);

		$tmp=$data->data->content;
		$app=array();
		if(isset($tmp->downloadlink)) {
			$app['downloadlink']=$tmp->downloadlink;
		}else{
			$app['downloadlink']='';
		}
		return $app;
	}

}
