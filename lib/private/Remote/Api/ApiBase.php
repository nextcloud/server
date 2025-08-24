<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote\Api;

use OCP\Http\Client\IClientService;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

class ApiBase {
	/** @var IInstance */
	private $instance;
	/** @var ICredentials */
	private $credentials;
	/** @var IClientService */
	private $clientService;

	public function __construct(IInstance $instance, ICredentials $credentials, IClientService $clientService) {
		$this->instance = $instance;
		$this->credentials = $credentials;
		$this->clientService = $clientService;
	}

	protected function getHttpClient() {
		return $this->clientService->newClient();
	}

	protected function addDefaultHeaders(array $headers) {
		return array_merge([
			'OCS-APIREQUEST' => 'true',
			'Accept' => 'application/json'
		], $headers);
	}

	/**
	 * @param string $method
	 * @param string $url
	 * @param array $body
	 * @param array $query
	 * @param array $headers
	 * @return resource|string
	 * @throws \InvalidArgumentException
	 */
	protected function request($method, $url, array $body = [], array $query = [], array $headers = []) {
		$fullUrl = trim($this->instance->getFullUrl(), '/') . '/' . $url;
		$options = [
			'query' => $query,
			'headers' => $this->addDefaultHeaders($headers),
			'auth' => [$this->credentials->getUsername(), $this->credentials->getPassword()]
		];
		if ($body) {
			$options['body'] = $body;
		}

		$client = $this->getHttpClient();

		switch ($method) {
			case 'get':
				$response = $client->get($fullUrl, $options);
				break;
			case 'post':
				$response = $client->post($fullUrl, $options);
				break;
			case 'put':
				$response = $client->put($fullUrl, $options);
				break;
			case 'delete':
				$response = $client->delete($fullUrl, $options);
				break;
			case 'options':
				$response = $client->options($fullUrl, $options);
				break;
			default:
				throw new \InvalidArgumentException('Invalid method ' . $method);
		}

		return $response->getBody();
	}
}
