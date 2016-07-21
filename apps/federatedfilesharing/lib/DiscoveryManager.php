<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\FederatedFileSharing;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;

/**
 * Class DiscoveryManager handles the discovery of endpoints used by Federated
 * Cloud Sharing.
 *
 * @package OCA\FederatedFileSharing
 */
class DiscoveryManager {
	/** @var ICache */
	private $cache;
	/** @var IClient */
	private $client;

	/**
	 * @param ICacheFactory $cacheFactory
	 * @param IClientService $clientService
	 */
	public function __construct(ICacheFactory $cacheFactory,
								IClientService $clientService) {
		$this->cache = $cacheFactory->create('ocs-discovery');
		$this->client = $clientService->newClient();
	}

	/**
	 * Returns whether the specified URL includes only safe characters, if not
	 * returns false
	 *
	 * @param string $url
	 * @return bool
	 */
	private function isSafeUrl($url) {
		return (bool)preg_match('/^[\/\.A-Za-z0-9]+$/', $url);
	}

	/**
	 * Discover the actual data and do some naive caching to ensure that the data
	 * is not requested multiple times.
	 *
	 * If no valid discovery data is found the Nextcloud defaults are returned.
	 *
	 * @param string $remote
	 * @return array
	 */
	private function discover($remote) {
		// Check if something is in the cache
		if($cacheData = $this->cache->get($remote)) {
			return json_decode($cacheData, true);
		}

		// Default response body
		$discoveredServices = [
			'webdav' => '/public.php/webdav',
			'share' => '/ocs/v1.php/cloud/shares',
		];

		// Read the data from the response body
		try {
			$response = $this->client->get($remote . '/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			]);
			if($response->getStatusCode() === 200) {
				$decodedService = json_decode($response->getBody(), true);
				if(is_array($decodedService)) {
					$endpoints = [
						'webdav',
						'share',
					];

					foreach($endpoints as $endpoint) {
						if(isset($decodedService['services']['FEDERATED_SHARING']['endpoints'][$endpoint])) {
							$endpointUrl = (string)$decodedService['services']['FEDERATED_SHARING']['endpoints'][$endpoint];
							if($this->isSafeUrl($endpointUrl)) {
								$discoveredServices[$endpoint] = $endpointUrl;
							}
						}
					}
				}
			}
		} catch (ClientException $e) {
			// Don't throw any exception since exceptions are handled before
		} catch (ConnectException $e) {
			// Don't throw any exception since exceptions are handled before
		}

		// Write into cache
		$this->cache->set($remote, json_encode($discoveredServices));
		return $discoveredServices;
	}

	/**
	 * Return the public WebDAV endpoint used by the specified remote
	 *
	 * @param string $host
	 * @return string
	 */
	public function getWebDavEndpoint($host) {
		return $this->discover($host)['webdav'];
	}

	/**
	 * Return the sharing endpoint used by the specified remote
	 *
	 * @param string $host
	 * @return string
	 */
	public function getShareEndpoint($host) {
		return $this->discover($host)['share'];
	}
}
