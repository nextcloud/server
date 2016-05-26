<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use OCP\Http\Client\IClient;
use OCP\ICertificateManager;
use OCP\IConfig;

/**
 * Class Client
 *
 * @package OC\Http
 */
class Client implements IClient {
	/** @var GuzzleClient */
	private $client;
	/** @var IConfig */
	private $config;
	/** @var ICertificateManager */
	private $certificateManager;
	private $configured = false;

	/**
	 * @param IConfig $config
	 * @param ICertificateManager $certificateManager
	 * @param GuzzleClient $client
	 */
	public function __construct(IConfig $config,
								ICertificateManager $certificateManager,
								GuzzleClient $client) {
		$this->config = $config;
		$this->client = $client;
		$this->certificateManager = $certificateManager;
	}

	/**
	 * Sets the default options to the client
	 */
	private function setDefaultOptions() {
		if ($this->configured) {
			return;
		}
		$this->configured = true;
		// Either use user bundle or the system bundle if nothing is specified
		if ($this->certificateManager->listCertificates() !== []) {
			$this->client->setDefaultOption('verify', $this->certificateManager->getAbsoluteBundlePath());
		} else {
			// If the instance is not yet setup we need to use the static path as
			// $this->certificateManager->getAbsoluteBundlePath() tries to instantiiate
			// a view
			if ($this->config->getSystemValue('installed', false)) {
				$this->client->setDefaultOption('verify', $this->certificateManager->getAbsoluteBundlePath(null));
			} else {
				$this->client->setDefaultOption('verify', \OC::$SERVERROOT . '/resources/config/ca-bundle.crt');
			}
		}

		$this->client->setDefaultOption('headers/User-Agent', 'ownCloud Server Crawler');
		if ($this->getProxyUri() !== '') {
			$this->client->setDefaultOption('proxy', $this->getProxyUri());
		}
	}

	/**
	 * Get the proxy URI
	 *
	 * @return string
	 */
	private function getProxyUri() {
		$proxyHost = $this->config->getSystemValue('proxy', null);
		$proxyUserPwd = $this->config->getSystemValue('proxyuserpwd', null);
		$proxyUri = '';

		if (!is_null($proxyUserPwd)) {
			$proxyUri .= $proxyUserPwd . '@';
		}
		if (!is_null($proxyHost)) {
			$proxyUri .= $proxyHost;
		}

		return $proxyUri;
	}

	/**
	 * Sends a GET request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'query' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function get($uri, array $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->get($uri, $options);
		$isStream = isset($options['stream']) && $options['stream'];
		return new Response($response, $isStream);
	}

	/**
	 * Sends a HEAD request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function head($uri, $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->head($uri, $options);
		return new Response($response);
	}

	/**
	 * Sends a POST request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function post($uri, array $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->post($uri, $options);
		return new Response($response);
	}

	/**
	 * Sends a PUT request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function put($uri, array $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->put($uri, $options);
		return new Response($response);
	}

	/**
	 * Sends a DELETE request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function delete($uri, array $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->delete($uri, $options);
		return new Response($response);
	}


	/**
	 * Sends a options request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *              'body' => [
	 *                  'field' => 'abc',
	 *                  'other_field' => '123',
	 *                  'file_name' => fopen('/path/to/file', 'r'),
	 *              ],
	 *              'headers' => [
	 *                  'foo' => 'bar',
	 *              ],
	 *              'cookies' => ['
	 *                  'foo' => 'bar',
	 *              ],
	 *              'allow_redirects' => [
	 *                   'max'       => 10,  // allow at most 10 redirects.
	 *                   'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                   'referer'   => true,     // add a Referer header
	 *                   'protocols' => ['https'] // only allow https URLs
	 *              ],
	 *              'save_to' => '/path/to/file', // save to a file or a stream
	 *              'verify' => true, // bool or string to CA file
	 *              'debug' => true,
	 *              'timeout' => 5,
	 * @return Response
	 * @throws \Exception If the request could not get completed
	 */
	public function options($uri, array $options = []) {
		$this->setDefaultOptions();
		$response = $this->client->options($uri, $options);
		return new Response($response);
	}
}
