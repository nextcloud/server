<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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


namespace OCA\Federation;


use OC\Files\Filesystem;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\ILogger;

class TrustedServers {

	/** @var  dbHandler */
	private $dbHandler;

	/** @var  IClientService */
	private $httpClientService;

	/** @var ILogger */
	private $logger;

	private $dbTable = 'trusted_servers';

	/**
	 * @param DbHandler $dbHandler
	 * @param IClientService $httpClientService
	 * @param ILogger $logger
	 */
	public function __construct(
		DbHandler $dbHandler,
		IClientService $httpClientService,
		ILogger $logger
	) {
		$this->dbHandler = $dbHandler;
		$this->httpClientService = $httpClientService;
		$this->logger = $logger;
	}

	/**
	 * add server to the list of trusted ownCloud servers
	 *
	 * @param $url
	 * @return int server id
	 */
	public function addServer($url) {
		return $this->dbHandler->add($this->normalizeUrl($url));
	}

	/**
	 * remove server from the list of trusted ownCloud servers
	 *
	 * @param int $id
	 */
	public function removeServer($id) {
		$this->dbHandler->remove($id);
	}

	/**
	 * get all trusted servers
	 *
	 * @return array
	 */
	public function getServers() {
		return $this->dbHandler->getAll();
	}

	/**
	 * check if given server is a trusted ownCloud server
	 *
	 * @param string $url
	 * @return bool
	 */
	public function isTrustedServer($url) {
		return $this->dbHandler->exists($this->normalizeUrl($url));
	}

	/**
	 * check if URL point to a ownCloud server
	 *
	 * @param string $url
	 * @return bool
	 */
	public function isOwnCloudServer($url) {
		$isValidOwnCloud = false;
		$client = $this->httpClientService->newClient();
		try {
			$result = $client->get(
				$url . '/status.php',
				[
					'timeout' => 3,
					'connect_timeout' => 3,
				]
			);
			if ($result->getStatusCode() === Http::STATUS_OK) {
				$isValidOwnCloud = $this->checkOwnCloudVersion($result->getBody());
			}
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['app' => 'federation']);
			return false;
		}
		return $isValidOwnCloud;
	}

	/**
	 * check if ownCloud version is >= 9.0
	 *
	 * @param $statusphp
	 * @return bool
	 */
	protected function checkOwnCloudVersion($statusphp) {
		$decoded = json_decode($statusphp, true);
		if (!empty($decoded) && isset($decoded['version'])) {
			return version_compare($decoded['version'], '9.0.0', '>=');
		}
		return false;
	}

	/**
	 * normalize URL
	 *
	 * @param string $url
	 * @return string
	 */
	protected function normalizeUrl($url) {

		$normalized = $url;

		if (strpos($url, 'https://') === 0) {
			$normalized = substr($url, strlen('https://'));
		} else if (strpos($url, 'http://') === 0) {
			$normalized = substr($url, strlen('http://'));
		}

		$normalized = Filesystem::normalizePath($normalized);
		$normalized = trim($normalized, '/');

		return $normalized;
	}
}
