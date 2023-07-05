<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

		$response = match ($method) {
			'get' => $client->get($fullUrl, $options),
			'post' => $client->post($fullUrl, $options),
			'put' => $client->put($fullUrl, $options),
			'delete' => $client->delete($fullUrl, $options),
			'options' => $client->options($fullUrl, $options),
			default => throw new \InvalidArgumentException('Invalid method ' . $method),
		};

		return $response->getBody();
	}
}
