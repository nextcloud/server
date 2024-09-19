<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Fetcher;

use OC\App\AppStore\Fetcher\AppDiscoverFetcher;
use OC\App\CompareVersion;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use PHPUnit\Framework\MockObject\MockObject;

class AppDiscoverFetcherTest extends FetcherBase {
	protected CompareVersion|MockObject $compareVersion;

	protected function setUp(): void {
		parent::setUp();
		$this->fileName = 'discover.json';
		$this->endpoint = 'https://apps.nextcloud.com/api/v1/discover.json';

		$this->compareVersion = $this->createMock(CompareVersion::class);

		$this->fetcher = new AppDiscoverFetcher(
			$this->appDataFactory,
			$this->clientService,
			$this->timeFactory,
			$this->config,
			$this->logger,
			$this->registry,
			$this->compareVersion,
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

	/**
	 * @dataProvider dataGetETag
	 */
	public function testGetEtag(?string $expected, bool $throws, string $content = ''): void {
		$folder = $this->createMock(ISimpleFolder::class);
		if (!$throws) {
			$file = $this->createMock(ISimpleFile::class);
			$file->expects($this->once())
				->method('getContent')
				->willReturn($content);
			$folder->expects($this->once())
				->method('getFile')
				->with('discover.json')
				->willReturn($file);
		} else {
			$folder->expects($this->once())
				->method('getFile')
				->with('discover.json')
				->willThrowException(new NotFoundException(''));
		}

		$this->appData->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);

		$etag = $this->fetcher->getETag();
		$this->assertEquals($expected, $etag);
		if ($expected !== null) {
			$this->assertTrue(gettype($etag) === 'string');
		}
	}

	public function dataGetETag(): array {
		return [
			'file not found' => [null, true],
			'empty file' => [null, false, ''],
			'missing etag' => [null, false, '{ "foo": "bar" }'],
			'valid etag' => ['test', false, '{ "ETag": "test" }'],
			'numeric etag' => ['132', false, '{ "ETag": 132 }'],
		];
	}
}
