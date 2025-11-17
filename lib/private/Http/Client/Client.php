<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IPromise;
use OCP\Http\Client\IResponse;
use OCP\Http\Client\LocalServerException;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;
use function parse_url;

/**
 * Class Client
 *
 * @package OC\Http
 */
class Client implements IClient {
	public function __construct(
		private IConfig $config,
		private ICertificateManager $certificateManager,
		private GuzzleClient $client,
		private IRemoteHostValidator $remoteHostValidator,
		protected LoggerInterface $logger,
		protected ServerVersion $serverVersion,
	) {
	}

	private function buildRequestOptions(array $options): array {
		$proxy = $this->getProxyUri();

		$defaults = [
			RequestOptions::VERIFY => $this->getCertBundle(),
			RequestOptions::TIMEOUT => IClient::DEFAULT_REQUEST_TIMEOUT,
		];

		$options['nextcloud']['allow_local_address'] = $this->isLocalAddressAllowed($options);
		if ($options['nextcloud']['allow_local_address'] === false) {
			$onRedirectFunction = function (
				\Psr\Http\Message\RequestInterface $request,
				\Psr\Http\Message\ResponseInterface $response,
				\Psr\Http\Message\UriInterface $uri,
			) use ($options): void {
				$this->preventLocalAddress($uri->__toString(), $options);
			};

			$defaults[RequestOptions::ALLOW_REDIRECTS] = [
				'on_redirect' => $onRedirectFunction
			];
		}

		// Only add RequestOptions::PROXY if Nextcloud is explicitly
		// configured to use a proxy. This is needed in order not to override
		// Guzzle default values.
		if ($proxy !== null) {
			$defaults[RequestOptions::PROXY] = $proxy;
		}

		$options = array_merge($defaults, $options);

		if (!isset($options[RequestOptions::HEADERS]['User-Agent'])) {
			$userAgent = 'Nextcloud-Server-Crawler/' . $this->serverVersion->getVersionString();
			$options[RequestOptions::HEADERS]['User-Agent'] = $userAgent;
		}

		if (!isset($options[RequestOptions::HEADERS]['Accept-Encoding'])) {
			$options[RequestOptions::HEADERS]['Accept-Encoding'] = 'gzip';
		}

		// Fallback for save_to
		if (isset($options['save_to'])) {
			$options['sink'] = $options['save_to'];
			unset($options['save_to']);
		}

		return $options;
	}

	private function getCertBundle(): string {
		// If the instance is not yet setup we need to use the static path as
		// $this->certificateManager->getAbsoluteBundlePath() tries to instantiate
		// a view
		if (!$this->config->getSystemValueBool('installed', false)) {
			return $this->certificateManager->getDefaultCertificatesBundlePath();
		}

		return $this->certificateManager->getAbsoluteBundlePath();
	}

	/**
	 * Returns a null or an associative array specifying the proxy URI for
	 * 'http' and 'https' schemes, in addition to a 'no' key value pair
	 * providing a list of host names that should not be proxied to.
	 *
	 * @return array|null
	 *
	 * The return array looks like:
	 * [
	 *   'http' => 'username:password@proxy.example.com',
	 *   'https' => 'username:password@proxy.example.com',
	 *   'no' => ['foo.com', 'bar.com']
	 * ]
	 *
	 */
	private function getProxyUri(): ?array {
		$proxyHost = $this->config->getSystemValueString('proxy', '');

		if ($proxyHost === '') {
			return null;
		}

		$proxyUserPwd = $this->config->getSystemValueString('proxyuserpwd', '');
		if ($proxyUserPwd !== '') {
			$proxyHost = $proxyUserPwd . '@' . $proxyHost;
		}

		$proxy = [
			'http' => $proxyHost,
			'https' => $proxyHost,
		];

		$proxyExclude = $this->config->getSystemValue('proxyexclude', []);
		if ($proxyExclude !== [] && $proxyExclude !== null) {
			$proxy['no'] = $proxyExclude;
		}

		return $proxy;
	}

	private function isLocalAddressAllowed(array $options) : bool {
		if (($options['nextcloud']['allow_local_address'] ?? false)
			|| $this->config->getSystemValueBool('allow_local_remote_servers', false)) {
			return true;
		}

		return false;
	}

	protected function preventLocalAddress(string $uri, array $options): void {
		$host = parse_url($uri, PHP_URL_HOST);
		if ($host === false || $host === null) {
			throw new LocalServerException('Could not detect any host');
		}

		if ($this->isLocalAddressAllowed($options)) {
			return;
		}

		if (!$this->remoteHostValidator->isValid($host)) {
			throw new LocalServerException('Host "' . $host . '" violates local access rules');
		}
	}

	/**
	 * Sends a GET request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function get(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('get', $uri, $this->buildRequestOptions($options));
		$isStream = isset($options['stream']) && $options['stream'];
		return new Response($response, $isStream);
	}

	/**
	 * Sends a HEAD request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function head(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('head', $uri, $this->buildRequestOptions($options));
		return new Response($response);
	}

	/**
	 * Sends a POST request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function post(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);

		if (isset($options['body']) && is_array($options['body'])) {
			$options['form_params'] = $options['body'];
			unset($options['body']);
		}
		$response = $this->client->request('post', $uri, $this->buildRequestOptions($options));
		$isStream = isset($options['stream']) && $options['stream'];
		return new Response($response, $isStream);
	}

	/**
	 * Sends a PUT request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function put(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('put', $uri, $this->buildRequestOptions($options));
		return new Response($response);
	}

	/**
	 * Sends a PATCH request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function patch(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('patch', $uri, $this->buildRequestOptions($options));
		return new Response($response);
	}

	/**
	 * Sends a DELETE request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function delete(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('delete', $uri, $this->buildRequestOptions($options));
		return new Response($response);
	}

	/**
	 * Sends an OPTIONS request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function options(string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request('options', $uri, $this->buildRequestOptions($options));
		return new Response($response);
	}

	/**
	 * Get the response of a Throwable thrown by the request methods when possible
	 *
	 * @param \Throwable $e
	 * @return IResponse
	 * @throws \Throwable When $e did not have a response
	 * @since 29.0.0
	 */
	public function getResponseFromThrowable(\Throwable $e): IResponse {
		if (method_exists($e, 'hasResponse') && method_exists($e, 'getResponse') && $e->hasResponse()) {
			return new Response($e->getResponse());
		}

		throw $e;
	}

	/**
	 * Sends a HTTP request
	 *
	 * @param string $method The HTTP method to use
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IResponse
	 * @throws \Exception If the request could not get completed
	 */
	public function request(string $method, string $uri, array $options = []): IResponse {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->request($method, $uri, $this->buildRequestOptions($options));
		$isStream = isset($options['stream']) && $options['stream'];
		return new Response($response, $isStream);
	}

	protected function wrapGuzzlePromise(PromiseInterface $promise): IPromise {
		return new GuzzlePromiseAdapter(
			$promise,
			$this->logger
		);
	}

	/**
	 * Sends an asynchronous GET request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'query' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function getAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->requestAsync('get', $uri, $this->buildRequestOptions($options));
		return $this->wrapGuzzlePromise($response);
	}

	/**
	 * Sends an asynchronous HEAD request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function headAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->requestAsync('head', $uri, $this->buildRequestOptions($options));
		return $this->wrapGuzzlePromise($response);
	}

	/**
	 * Sends an asynchronous POST request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function postAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);

		if (isset($options['body']) && is_array($options['body'])) {
			$options['form_params'] = $options['body'];
			unset($options['body']);
		}

		return $this->wrapGuzzlePromise($this->client->requestAsync('post', $uri, $this->buildRequestOptions($options)));
	}

	/**
	 * Sends an asynchronous PUT request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function putAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->requestAsync('put', $uri, $this->buildRequestOptions($options));
		return $this->wrapGuzzlePromise($response);
	}

	/**
	 * Sends an asynchronous DELETE request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function deleteAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->requestAsync('delete', $uri, $this->buildRequestOptions($options));
		return $this->wrapGuzzlePromise($response);
	}

	/**
	 * Sends an asynchronous OPTIONS request
	 *
	 * @param string $uri
	 * @param array $options Array such as
	 *                       'body' => [
	 *                       'field' => 'abc',
	 *                       'other_field' => '123',
	 *                       'file_name' => fopen('/path/to/file', 'r'),
	 *                       ],
	 *                       'headers' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'cookies' => [
	 *                       'foo' => 'bar',
	 *                       ],
	 *                       'allow_redirects' => [
	 *                       'max'       => 10,  // allow at most 10 redirects.
	 *                       'strict'    => true,     // use "strict" RFC compliant redirects.
	 *                       'referer'   => true,     // add a Referer header
	 *                       'protocols' => ['https'] // only allow https URLs
	 *                       ],
	 *                       'sink' => '/path/to/file', // save to a file or a stream
	 *                       'verify' => true, // bool or string to CA file
	 *                       'debug' => true,
	 *                       'timeout' => 5,
	 * @return IPromise
	 */
	public function optionsAsync(string $uri, array $options = []): IPromise {
		$this->preventLocalAddress($uri, $options);
		$response = $this->client->requestAsync('options', $uri, $this->buildRequestOptions($options));
		return $this->wrapGuzzlePromise($response);
	}
}
