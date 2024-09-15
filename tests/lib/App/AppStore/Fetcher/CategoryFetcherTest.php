<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Fetcher;

use OC\App\AppStore\Fetcher\CategoryFetcher;

class CategoryFetcherTest extends FetcherBase {
	protected function setUp(): void {
		parent::setUp();
		$this->fileName = 'categories.json';
		$this->endpoint = 'https://apps.nextcloud.com/api/v1/categories.json';

		$this->fetcher = new CategoryFetcher(
			$this->appDataFactory,
			$this->clientService,
			$this->timeFactory,
			$this->config,
			$this->logger,
			$this->registry
		);
	}

	public function testAppstoreDisabled(): void {
		$this->config
			->method('getSystemValueBool')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'appstoreenabled') {
					return false;
				}
				return $default;
			});
		$this->appData
			->expects($this->never())
			->method('getFolder');

		$this->assertEquals([], $this->fetcher->get());
	}

	public function testNoInternet(): void {
		$this->config
			->method('getSystemValueBool')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'has_internet_connection') {
					return false;
				}
				return $default;
			});
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($var, $default) {
				return $default;
			});
		$this->appData
			->expects($this->never())
			->method('getFolder');

		$this->assertEquals([], $this->fetcher->get());
	}
}
