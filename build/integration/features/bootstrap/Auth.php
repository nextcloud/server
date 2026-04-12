<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use GuzzleHttp\Cookie\CookieJar;

require __DIR__ . '/autoload.php';

trait Auth {
	private string $unrestrictedClientToken;
	private string $restrictedClientToken;
	private Client $client;
	private string $responseXml;

	/** @BeforeScenario */
	public function setUpScenario() {
		$this->client = $this->getGuzzleClient(null);
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
		if ($useCookies) {
			$options['cookies'] = $this->cookieJar;
		}
		if ($authHeader) {
			$options['headers'] = [ 'Authorization' => $authHeader ];
		}
		$options['headers']['OCS_APIREQUEST'] = 'true';
		$options['headers']['requesttoken'] = $this->requestToken;
		$this->response = $this->client->request($method, $fullUrl, $options);
	}

	/**
	 * @When the CSRF token is extracted from the previous response
	 */
	public function theCsrfTokenIsExtractedFromThePreviousResponse() {
		$this->requestToken = substr(preg_replace('/(.*)data-requesttoken="(.*)">(.*)/sm', '\2', $this->response->getBody()->getContents()), 0, 89);
	}

	/**
	 * @return object
	 */
	private function createClientToken(bool $loginViaWeb = true) {
		if ($loginViaWeb) {
			$this->loggingInUsingWebAs('user0');
		}

		$fullUrl = substr($this->baseUrl, 0, -5) . '/index.php/settings/personal/authtokens';
		$client = $this->getGuzzleClient(null);
		$options['auth'] => [ 'user0', $loginViaWeb ? '123456' : $this->restrictedClientToken ];
		$options['form_params'] = [ 'requesttoken' => $this->requestToken, 'name' => md5(microtime()) ];
		$options['cookies'] = $this->cookieJar;

		$this->response = $client->request('POST', $fullUrl, $options);
		return json_decode($this->response->getBody()->getContents());
	}

	/**
	 * @Given a new restricted client token is added
	 */
	public function aNewRestrictedClientTokenIsAdded() {
		$tokenObj = $this->createClientToken();
		$newCreatedTokenId = $tokenObj->deviceToken->id;
		$fullUrl = substr($this->baseUrl, 0, -5) . '/index.php/settings/personal/authtokens/' . $newCreatedTokenId;
		$client = $this->getGuzzleClient(null);
		$options['auth'] = [ 'user0', '123456' ];
		$options['headers'] = [ 'requesttoken' => $this->requestToken ];
		$options['json'] = [
			'name' => md5(microtime()),
			'scope' => [ 'filesystem' => false ],
		];
		$options['cookies'] = $this->cookieJar;

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
		$client = $this->getGuzzleClient(null);
		$options['cookies'] = $this->cookieJar;
		$response = $client->get($loginUrl, $options);
		$this->extractRequestTokenFromResponse($response);

		// Login and extract new token
		$client = $this->getGuzzleClient(null);
		$options['form_params'] = [
			'user' => 'user0',
			'password' => '123456',
			'rememberme' => $remember ? '1' : '0',
			'requesttoken' => $this->requestToken,
		];
		$options['cookies'] = $this->cookieJar;
		$options['headers'] = [ 'Origin' => $baseUrl ];
		$response = $client->post($loginUrl, $options);
		$this->extractRequestTokenFromResponse($response);
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
