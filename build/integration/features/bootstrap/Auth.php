<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

require __DIR__ . '/../../vendor/autoload.php';

trait Auth {
	/** @var string */
	private $unrestrictedClientToken;
	/** @var string */
	private $restrictedClientToken;
	/** @var Client */
	private $client;
	/** @var string */
	private $responseXml;

	/** @BeforeScenario */
	public function setUpScenario() {
		$this->client = new Client();
		$this->responseXml = '';
		$this->cookieJar = new CookieJar();
	}

	/**
	 * @When requesting :url with :method
	 */
	public function requestingWith($url, $method) {
		$this->sendRequest($url, $method);
	}

	private function sendRequest($url, $method, $authHeader = null, $useCookies = false) {
		$fullUrl = substr($this->baseUrl, 0, -5) . $url;
		try {
			if ($useCookies) {
				$options = [
					'cookies' => $this->cookieJar,
				];
			} else {
				$options = [];
			}
			if ($authHeader) {
				$options['headers'] = [
					'Authorization' => $authHeader
				];
			}
			$options['headers']['OCS_APIREQUEST'] = 'true';
			$options['headers']['requesttoken'] = $this->requestToken;
			$this->response = $this->client->request($method, $fullUrl, $options);
		} catch (ClientException $ex) {
			$this->response = $ex->getResponse();
		} catch (ServerException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * @When the CSRF token is extracted from the previous response
	 */
	public function theCsrfTokenIsExtractedFromThePreviousResponse() {
		$this->requestToken = substr(preg_replace('/(.*)data-requesttoken="(.*)">(.*)/sm', '\2', $this->response->getBody()->getContents()), 0, 89);
	}

	/**
	 * @param bool $loginViaWeb
	 * @return object
	 */
	private function createClientToken($loginViaWeb = true) {
		if ($loginViaWeb) {
			$this->loggingInUsingWebAs('user0');
		}

		$fullUrl = substr($this->baseUrl, 0, -5) . '/index.php/settings/personal/authtokens';
		$client = new Client();
		$options = [
			'auth' => [
				'user0',
				$loginViaWeb ? '123456' : $this->restrictedClientToken,
			],
			'form_params' => [
				'requesttoken' => $this->requestToken,
				'name' => md5(microtime()),
			],
			'cookies' => $this->cookieJar,
		];

		try {
			$this->response = $client->request('POST', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			$this->response = $e->getResponse();
		}
		return json_decode($this->response->getBody()->getContents());
	}

	/**
	 * @Given a new restricted client token is added
	 */
	public function aNewRestrictedClientTokenIsAdded() {
		$tokenObj = $this->createClientToken();
		$newCreatedTokenId = $tokenObj->deviceToken->id;
		$fullUrl = substr($this->baseUrl, 0, -5) . '/index.php/settings/personal/authtokens/' . $newCreatedTokenId;
		$client = new Client();
		$options = [
			'auth' => ['user0', '123456'],
			'headers' => [
				'requesttoken' => $this->requestToken,
			],
			'json' => [
				'name' => md5(microtime()),
				'scope' => [
					'filesystem' => false,
				],
			],
			'cookies' => $this->cookieJar,
		];
		$this->response = $client->request('PUT', $fullUrl, $options);
		$this->restrictedClientToken = $tokenObj->token;
	}

	/**
	 * @Given a new unrestricted client token is added
	 */
	public function aNewUnrestrictedClientTokenIsAdded() {
		$this->unrestrictedClientToken = $this->createClientToken()->token;
	}

	/**
	 * @When a new unrestricted client token is added using restricted basic token auth
	 */
	public function aNewUnrestrictedClientTokenIsAddedUsingRestrictedBasicTokenAuth() {
		$this->createClientToken(false);
	}

	/**
	 * @When requesting :url with :method using basic auth
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithBasicAuth($url, $method) {
		$this->sendRequest($url, $method, 'basic ' . base64_encode('user0:123456'));
	}

	/**
	 * @When requesting :url with :method using unrestricted basic token auth
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithUnrestrictedBasicTokenAuth($url, $method) {
		$this->sendRequest($url, $method, 'basic ' . base64_encode('user0:' . $this->unrestrictedClientToken), true);
	}

	/**
	 * @When requesting :url with :method using restricted basic token auth
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithRestrictedBasicTokenAuth($url, $method) {
		$this->sendRequest($url, $method, 'basic ' . base64_encode('user0:' . $this->restrictedClientToken), true);
	}

	/**
	 * @When requesting :url with :method using an unrestricted client token
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithUsingAnUnrestrictedClientToken($url, $method) {
		$this->sendRequest($url, $method, 'Bearer ' . $this->unrestrictedClientToken);
	}

	/**
	 * @When requesting :url with :method using a restricted client token
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithUsingARestrictedClientToken($url, $method) {
		$this->sendRequest($url, $method, 'Bearer ' . $this->restrictedClientToken);
	}

	/**
	 * @When requesting :url with :method using browser session
	 *
	 * @param string $url
	 * @param string $method
	 */
	public function requestingWithBrowserSession($url, $method) {
		$this->sendRequest($url, $method, null, true);
	}

	/**
	 * @Given a new browser session is started
	 *
	 * @param bool $remember
	 */
	public function aNewBrowserSessionIsStarted($remember = false) {
		$baseUrl = substr($this->baseUrl, 0, -5);
		$loginUrl = $baseUrl . '/login';
		// Request a new session and extract CSRF token
		$client = new Client();
		$response = $client->get($loginUrl, [
			'cookies' => $this->cookieJar,
		]);
		$this->extracRequestTokenFromResponse($response);

		// Login and extract new token
		$client = new Client();
		$response = $client->post(
			$loginUrl, [
				'form_params' => [
					'user' => 'user0',
					'password' => '123456',
					'remember_login' => $remember ? '1' : '0',
					'requesttoken' => $this->requestToken,
				],
				'cookies' => $this->cookieJar,
				'headers' => [
					'Origin' => $baseUrl,
				],
			]
		);
		$this->extracRequestTokenFromResponse($response);
	}

	/**
	 * @Given a new remembered browser session is started
	 */
	public function aNewRememberedBrowserSessionIsStarted() {
		$this->aNewBrowserSessionIsStarted(true);
	}


	/**
	 * @Given the cookie jar is reset
	 */
	public function theCookieJarIsReset() {
		$this->cookieJar = new CookieJar();
	}

	/**
	 * @When the session cookie expires
	 */
	public function whenTheSessionCookieExpires() {
		$this->cookieJar->clearSessionCookies();
	}
}
