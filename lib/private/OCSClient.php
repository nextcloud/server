<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Jarrett <JetUni@users.noreply.github.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\ILogger;

/**
 * Class OCSClient is a class for communication with the ownCloud appstore
 *
 * @package OC
 */
class OCSClient {
	/** @var IClientService */
	private $httpClientService;
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;

	/**
	 * @param IClientService $httpClientService
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IClientService $httpClientService,
								IConfig $config,
								ILogger $logger) {
		$this->httpClientService = $httpClientService;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Returns whether the AppStore is enabled (i.e. because the AppStore is disabled for EE)
	 *
	 * @return bool
	 */
	public function isAppStoreEnabled() {
		// For a regular edition default to true, all others default to false
		$default = false;
		if (\OC_Util::getEditionString() === '') {
			$default = true;
		}

		return $this->config->getSystemValue('appstoreenabled', $default) === true;
	}

	/**
	 * Get the url of the OCS AppStore server.
	 *
	 * @return string of the AppStore server
	 */
	private function getAppStoreUrl() {
		return $this->config->getSystemValue('appstoreurl', 'https://api.owncloud.com/v1');
	}

	/**
	 * @param string $body
	 * @param string $action
	 * @return null|\SimpleXMLElement
	 */
	private function loadData($body, $action) {
		$loadEntities = libxml_disable_entity_loader(true);
		$data = @simplexml_load_string($body);
		libxml_disable_entity_loader($loadEntities);

		if($data === false) {
			libxml_clear_errors();
			$this->logger->error(
				sprintf('Could not get %s, content was no valid XML', $action),
				[
					'app' => 'core',
				]
			);
			return null;
		}

		return $data;
	}

	/**
	 * Get all the categories from the OCS server
	 *
	 * @param array $targetVersion The target ownCloud version
	 * @return array|null an array of category ids or null
	 * @note returns NULL if config value appstoreenabled is set to false
	 * This function returns a list of all the application categories on the OCS server
	 */
	public function getCategories(array $targetVersion) {
		if (!$this->isAppStoreEnabled()) {
			return null;
		}

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get(
				$this->getAppStoreUrl() . '/content/categories',
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', $targetVersion),
					],
				]
			);
		} catch(\Exception $e) {
			$this->logger->error(
				sprintf('Could not get categories: %s', $e->getMessage()),
				[
					'app' => 'core',
				]
			);
			return null;
		}

		$data = $this->loadData($response->getBody(), 'categories');
		if($data === null) {
			return null;
		}

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
	 * @param array $categories
	 * @param int $page
	 * @param string $filter
	 * @param array $targetVersion The target ownCloud version
	 * @return array An array of application data
	 */
	public function getApplications(array $categories, $page, $filter, array $targetVersion) {
		if (!$this->isAppStoreEnabled()) {
			return [];
		}

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get(
				$this->getAppStoreUrl() . '/content/data',
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', $targetVersion),
						'filter' => $filter,
						'categories' => implode('x', $categories),
						'sortmode' => 'new',
						'page' => $page,
						'pagesize' => 100,
						'approved' => $filter
					],
				]
			);
		} catch(\Exception $e) {
			$this->logger->error(
				sprintf('Could not get applications: %s', $e->getMessage()),
				[
					'app' => 'core',
				]
			);
			return [];
		}

		$data = $this->loadData($response->getBody(), 'applications');
		if($data === null) {
			return [];
		}

		$tmp = $data->data->content;
		$tmpCount = count($tmp);

		$apps = [];
		for ($i = 0; $i < $tmpCount; $i++) {
			$app = [];
			$app['id'] = (string)$tmp[$i]->id;
			$app['name'] = (string)$tmp[$i]->name;
			$app['label'] = (string)$tmp[$i]->label;
			$app['version'] = (string)$tmp[$i]->version;
			$app['type'] = (string)$tmp[$i]->typeid;
			$app['typename'] = (string)$tmp[$i]->typename;
			$app['personid'] = (string)$tmp[$i]->personid;
			$app['profilepage'] = (string)$tmp[$i]->profilepage;
			$app['license'] = (string)$tmp[$i]->license;
			$app['detailpage'] = (string)$tmp[$i]->detailpage;
			$app['preview'] = (string)$tmp[$i]->smallpreviewpic1;
			$app['preview-full'] = (string)$tmp[$i]->previewpic1;
			$app['changed'] = strtotime($tmp[$i]->changed);
			$app['description'] = (string)$tmp[$i]->description;
			$app['score'] = (string)$tmp[$i]->score;
			$app['downloads'] = (int)$tmp[$i]->downloads;
			$app['level'] = (int)$tmp[$i]->approved;

			$apps[] = $app;
		}

		return $apps;
	}


	/**
	 * Get an the applications from the OCS server
	 *
	 * @param string $id
	 * @param array $targetVersion The target ownCloud version
	 * @return array|null an array of application data or null
	 *
	 * This function returns an applications from the OCS server
	 */
	public function getApplication($id, array $targetVersion) {
		if (!$this->isAppStoreEnabled()) {
			return null;
		}

		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get(
				$this->getAppStoreUrl() . '/content/data/' . urlencode($id),
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', $targetVersion),
					],
				]
			);
		} catch(\Exception $e) {
			$this->logger->error(
				sprintf('Could not get application: %s', $e->getMessage()),
				[
					'app' => 'core',
				]
			);
			return null;
		}

		$data = $this->loadData($response->getBody(), 'application');
		if($data === null) {
			return null;
		}

		$tmp = $data->data->content;
		if (is_null($tmp)) {
			\OCP\Util::writeLog('core', 'No update found at the ownCloud appstore for app ' . $id, \OCP\Util::DEBUG);
			return null;
		}

		$app = [];
		$app['id'] = (int)$id;
		$app['name'] = (string)$tmp->name;
		$app['version'] = (string)$tmp->version;
		$app['type'] = (string)$tmp->typeid;
		$app['label'] = (string)$tmp->label;
		$app['typename'] = (string)$tmp->typename;
		$app['personid'] = (string)$tmp->personid;
		$app['profilepage'] = (string)$tmp->profilepage;
		$app['detailpage'] = (string)$tmp->detailpage;
		$app['preview1'] = (string)$tmp->smallpreviewpic1;
		$app['preview2'] = (string)$tmp->smallpreviewpic2;
		$app['preview3'] = (string)$tmp->smallpreviewpic3;
		$app['changed'] = strtotime($tmp->changed);
		$app['description'] = (string)$tmp->description;
		$app['detailpage'] = (string)$tmp->detailpage;
		$app['score'] = (int)$tmp->score;
		$app['level'] = (int)$tmp->approved;

		return $app;
	}

	/**
	 * Get the download url for an application from the OCS server
	 * @param string $id
	 * @param array $targetVersion The target ownCloud version
	 * @return array|null an array of application data or null
	 */
	public function getApplicationDownload($id, array $targetVersion) {
		if (!$this->isAppStoreEnabled()) {
			return null;
		}
		$url = $this->getAppStoreUrl() . '/content/download/' . urlencode($id) . '/1';
		$client = $this->httpClientService->newClient();
		try {
			$response = $client->get(
				$url,
				[
					'timeout' => 20,
					'query' => [
						'version' => implode('x', $targetVersion),
					],
				]
			);
		} catch(\Exception $e) {
			$this->logger->error(
				sprintf('Could not get application download URL: %s', $e->getMessage()),
				[
					'app' => 'core',
				]
			);
			return null;
		}

		$data = $this->loadData($response->getBody(), 'application download URL');
		if($data === null) {
			return null;
		}

		$tmp = $data->data->content;
		$app = [];
		if (isset($tmp->downloadlink)) {
			$app['downloadlink'] = (string)$tmp->downloadlink;
		} else {
			$app['downloadlink'] = '';
		}
		return $app;
	}

}
