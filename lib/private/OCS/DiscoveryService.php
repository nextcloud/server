<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\OCS;

use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\OCS\IDiscoveryService;

class DiscoveryService implements IDiscoveryService {

	/** @var ICache */
	private $cache;

	/** @var IClient */
	private $client;

	/**
	 * @param ICacheFactory $cacheFactory
	 * @param IClientService $clientService
	 */
	public function __construct(ICacheFactory $cacheFactory,
								IClientService $clientService
	) {
		$this->cache = $cacheFactory->createDistributed('ocs-discovery');
		$this->client = $clientService->newClient();
	}


	/**
	 * Discover OCS end-points
	 *
	 * If no valid discovery data is found the defaults are returned
	 *
	 * @param string $remote
	 * @param string $service the service you want to discover
	 * @return array
	 */
	public function discover($remote, $service) {
		// Check the cache first
		$cacheData = $this->cache->get($remote . '#' . $service);
		if($cacheData) {
			return json_decode($cacheData, true);
		}

		$discoveredServices = [];

		// query the remote server for available services
		try {
			$response = $this->client->get($remote . '/ocs-provider/', [
				'timeout' => 10,
				'connect_timeout' => 10,
			]);
			if($response->getStatusCode() === Http::STATUS_OK) {
				$decodedServices = json_decode($response->getBody(), true);
				$discoveredServices = $this->getEndpoints($decodedServices, $service);
			}
		} catch (\Exception $e) {
			// if we couldn't discover the service or any end-points we return a empty array
		}

		// Write into cache
		$this->cache->set($remote . '#' . $service, json_encode($discoveredServices), 60*60*24);
		return $discoveredServices;
	}

	/**
	 * get requested end-points from the requested service
	 *
	 * @param $decodedServices
	 * @param $service
	 * @return array
	 */
	protected function getEndpoints($decodedServices, $service) {

		$discoveredServices = [];

		if(is_array($decodedServices) &&
			isset($decodedServices['services'][$service]['endpoints'])
		) {
			foreach ($decodedServices['services'][$service]['endpoints'] as $endpoint => $url) {
				if($this->isSafeUrl($url)) {
					$discoveredServices[$endpoint] = $url;
				}
			}
		}

		return $discoveredServices;
	}

	/**
	 * Returns whether the specified URL includes only safe characters, if not
	 * returns false
	 *
	 * @param string $url
	 * @return bool
	 */
	protected function isSafeUrl($url) {
		return (bool)preg_match('/^[\/\.\-A-Za-z0-9]+$/', $url);
	}

}
