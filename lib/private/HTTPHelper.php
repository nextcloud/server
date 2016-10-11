<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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

/**
 * Class HTTPHelper
 *
 * @package OC
 * @deprecated Use \OCP\Http\Client\IClientService
 */
class HTTPHelper {
	const USER_AGENT = 'ownCloud Server Crawler';

	/** @var \OCP\IConfig */
	private $config;
	/** @var IClientService  */
	private $clientService;

	/**
	 * @param IConfig $config
	 * @param IClientService $clientService
	 */
	public function __construct(IConfig $config,
								IClientService $clientService) {
		$this->config = $config;
		$this->clientService = $clientService;
	}

	/**
	 * Get URL content
	 * @param string $url Url to get content
	 * @throws \Exception If the URL does not start with http:// or https://
	 * @return string of the response or false on error
	 * This function get the content of a page via curl, if curl is enabled.
	 * If not, file_get_contents is used.
	 * @deprecated Use \OCP\Http\Client\IClientService
	 */
	public function getUrlContent($url) {
		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url);
			return $response->getBody();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Returns the response headers of a HTTP URL without following redirects
	 * @param string $location Needs to be a HTTPS or HTTP URL
	 * @return array
	 * @deprecated Use \OCP\Http\Client\IClientService
	 */
	public function getHeaders($location) {
		$client = $this->clientService->newClient();
		$response = $client->get($location);
		return $response->getHeaders();
	}

	/**
	 * Checks whether the supplied URL begins with HTTPS:// or HTTP:// (case insensitive)
	 * @param string $url
	 * @return bool
	 */
	public function isHTTPURL($url) {
		return stripos($url, 'https://') === 0 || stripos($url, 'http://') === 0;
	}

	/**
	 * send http post request
	 *
	 * @param string $url
	 * @param array $fields data send by the request
	 * @return array
	 * @deprecated Use \OCP\Http\Client\IClientService
	 */
	public function post($url, array $fields) {
		$client = $this->clientService->newClient();

		try {
			$response = $client->post(
				$url,
				[
					'body' => $fields,
					'connect_timeout' => 10,
				]
			);
		} catch (\Exception $e) {
			return ['success' => false, 'result' => $e->getMessage()];
		}

		return ['success' => true, 'result' => $response->getBody()];
	}

}
