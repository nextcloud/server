<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;

trait ClientServiceTrait {
	/** @var IClientService|\PHPUnit\Framework\MockObject\MockObject */
	private $clientService;
	/** @var IClient|\PHPUnit\Framework\MockObject\MockObject */
	private $client;
	private $expectedGetRequests = [];
	private $expectedPostRequests = [];

	/**
	 * Wrapper to be forward compatible to phpunit 5.4+
	 *
	 * @param string $originalClassName
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	abstract protected function createMock(string $originalClassName);

	/**
	 * Returns a matcher that matches when the method is executed
	 * zero or more times.
	 *
	 * @return \PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount
	 *
	 * @since  Method available since Release 3.0.0
	 */
	abstract public static function any();

	protected function setUpClientServiceTrait() {
		$this->clientService = $this->createMock(IClientService::class);
		$this->client = $this->createMock(IClient::class);
		$this->clientService->expects($this->any())
			->method('newClient')
			->willReturn($this->client);
		$this->client->expects($this->any())
			->method('get')
			->willReturnCallback(function ($url) {
				if (!isset($this->expectedGetRequests[$url])) {
					throw new \Exception('unexpected request: ' . $url);
				}
				$result = $this->expectedGetRequests[$url];

				if ($result instanceof \Exception) {
					throw $result;
				} else {
					$response = $this->createMock(IResponse::class);
					$response->expects($this->any())
						->method('getBody')
						->willReturn($result);
					return $response;
				}
			});
		$this->client->expects($this->any())
			->method('post')
			->willReturnCallback(function ($url) {
				if (!isset($this->expectedPostRequests[$url])) {
					throw new \Exception('unexpected request: ' . $url);
				}
				$result = $this->expectedPostRequests[$url];

				if ($result instanceof \Exception) {
					throw $result;
				} else {
					$response = $this->createMock(IResponse::class);
					$response->expects($this->any())
						->method('getBody')
						->willReturn($result);
					return $response;
				}
			});
	}

	/**
	 * @param string $url
	 * @param string|\Exception $result
	 */
	protected function expectGetRequest($url, $result) {
		$this->expectedGetRequests[$url] = $result;
	}

	/**
	 * @param string $url
	 * @param string|\Exception $result
	 */
	protected function expectPostRequest($url, $result) {
		$this->expectedPostRequests[$url] = $result;
	}

	/**
	 * @return IClientService|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getClientService() {
		return $this->clientService;
	}
}
