<?php
/**
 * Copyright (c) 2014-2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
			$response = $client->post($url, ['body' => $fields]);
		} catch (\Exception $e) {
			return ['success' => false, 'result' => $e->getMessage()];
		}

		return ['success' => true, 'result' => $response->getBody()];
	}

}
