<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

/**
 * This class provides an easy way for apps to store config values in the
 * database.
 */

class OCSClient {

	/**
	 * Returns whether the AppStore is enabled (i.e. because the AppStore is disabled for EE)
	 *
	 * @return bool
	 */
	public static function isAppStoreEnabled() {
		if (\OC::$server->getConfig()->getSystemValue('appstoreenabled', true) === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the url of the OCS AppStore server.
	 *
	 * @return string of the AppStore server
	 *
	 * This function returns the url of the OCS AppStore server. It´s possible
	 * to set it in the config file or it will fallback to the default
	 */
	private static function getAppStoreURL() {
		return \OC::$server->getConfig()->getSystemValue('appstoreurl', 'https://api.owncloud.com/v1');
	}

	/**
	 * Get all the categories from the OCS server
	 *
	 * @return array|null an array of category ids or null
	 * @note returns NULL if config value appstoreenabled is set to false
	 * This function returns a list of all the application categories on the OCS server
	 */
	public static function getCategories() {
		if (!self::isAppStoreEnabled()) {
			return null;
		}
		$url = self::getAppStoreURL() . '/content/categories';

		$client = \OC::$server->getHTTPClientService()->newClient();
		try {
			$response = $client->get($url, ['timeout' => 5]);
		} catch(\Exception $e) {
			return null;
		}

		if($response->getStatusCode() !== 200) {
			return null;
		}

		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($response->getBody());
		libxml_disable_entity_loader($loadEntities);

		$tmp = $data->data;
		$cats = [];

		foreach ($tmp->category as $value) {

			$id = (int)$value->id;
			$name = (string)$value->name;
			$cats[$id] = $name;

		}

		return $cats;
	}

	/**
	 * Get all the applications from the OCS server
	 *
	 * @return array|null an array of application data or null
	 *
	 * This function returns a list of all the applications on the OCS server
	 * @param array|string $categories
	 * @param int $page
	 * @param string $filter
	 */
	public static function getApplications($categories, $page, $filter) {
		if (!self::isAppStoreEnabled()) {
			return (array());
		}

		if (is_array($categories)) {
			$categoriesString = implode('x', $categories);
		} else {
			$categoriesString = $categories;
		}

		$version = '&version=' . implode('x', \OC_Util::getVersion());
		$filterUrl = '&filter=' . urlencode($filter);
		$url = self::getAppStoreURL() . '/content/data?categories=' . urlencode($categoriesString)
			. '&sortmode=new&page=' . urlencode($page) . '&pagesize=100' . $filterUrl . $version;
		$apps = [];

		$client = \OC::$server->getHTTPClientService()->newClient();
		try {
			$response = $client->get($url, ['timeout' => 5]);
		} catch(\Exception $e) {
			return null;
		}

		if($response->getStatusCode() !== 200) {
			return null;
		}

		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($response->getBody());
		libxml_disable_entity_loader($loadEntities);

		$tmp = $data->data->content;
		$tmpCount = count($tmp);
		for ($i = 0; $i < $tmpCount; $i++) {
			$app = array();
			$app['id'] = (string)$tmp[$i]->id;
			$app['name'] = (string)$tmp[$i]->name;
			$app['label'] = (string)$tmp[$i]->label;
			$app['version'] = (string)$tmp[$i]->version;
			$app['type'] = (string)$tmp[$i]->typeid;
			$app['typename'] = (string)$tmp[$i]->typename;
			$app['personid'] = (string)$tmp[$i]->personid;
			$app['license'] = (string)$tmp[$i]->license;
			$app['detailpage'] = (string)$tmp[$i]->detailpage;
			$app['preview'] = (string)$tmp[$i]->smallpreviewpic1;
			$app['preview-full'] = (string)$tmp[$i]->previewpic1;
			$app['changed'] = strtotime($tmp[$i]->changed);
			$app['description'] = (string)$tmp[$i]->description;
			$app['score'] = (string)$tmp[$i]->score;
			$app['downloads'] = (int)$tmp[$i]->downloads;

			$apps[] = $app;
		}
		return $apps;
	}


	/**
	 * Get an the applications from the OCS server
	 *
	 * @param string $id
	 * @return array|null an array of application data or null
	 *
	 * This function returns an applications from the OCS server
	 */
	public static function getApplication($id) {
		if (!self::isAppStoreEnabled()) {
			return null;
		}
		$url = self::getAppStoreURL() . '/content/data/' . urlencode($id);
		$client = \OC::$server->getHTTPClientService()->newClient();
		try {
			$response = $client->get($url, ['timeout' => 5]);
		} catch(\Exception $e) {
			return null;
		}

		if($response->getStatusCode() !== 200) {
			return null;
		}

		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($response->getBody());
		libxml_disable_entity_loader($loadEntities);

		$tmp = $data->data->content;
		if (is_null($tmp)) {
			\OC_Log::write('core', 'Invalid OCS content returned for app ' . $id, \OC_Log::FATAL);
			return null;
		}
		$app = [];
		$app['id'] = $tmp->id;
		$app['name'] = $tmp->name;
		$app['version'] = $tmp->version;
		$app['type'] = $tmp->typeid;
		$app['label'] = $tmp->label;
		$app['typename'] = $tmp->typename;
		$app['personid'] = $tmp->personid;
		$app['detailpage'] = $tmp->detailpage;
		$app['preview1'] = $tmp->smallpreviewpic1;
		$app['preview2'] = $tmp->smallpreviewpic2;
		$app['preview3'] = $tmp->smallpreviewpic3;
		$app['changed'] = strtotime($tmp->changed);
		$app['description'] = $tmp->description;
		$app['detailpage'] = $tmp->detailpage;
		$app['score'] = $tmp->score;

		return $app;
	}

	/**
	 * Get the download url for an application from the OCS server
	 *
	 * @return array|null an array of application data or null
	 *
	 * This function returns an download url for an applications from the OCS server
	 * @param string $id
	 * @param integer $item
	 */
	public static function getApplicationDownload($id, $item) {
		if (!self::isAppStoreEnabled()) {
			return null;
		}
		$url = self::getAppStoreURL() . '/content/download/' . urlencode($id) . '/' . urlencode($item);
		$client = \OC::$server->getHTTPClientService()->newClient();
		try {
			$response = $client->get($url, ['timeout' => 5]);
		} catch(\Exception $e) {
			return null;
		}

		if($response->getStatusCode() !== 200) {
			return null;
		}

		$loadEntities = libxml_disable_entity_loader(true);
		$data = simplexml_load_string($response->getBody());
		libxml_disable_entity_loader($loadEntities);

		$tmp = $data->data->content;
		$app = array();
		if (isset($tmp->downloadlink)) {
			$app['downloadlink'] = $tmp->downloadlink;
		} else {
			$app['downloadlink'] = '';
		}
		return $app;
	}

}
