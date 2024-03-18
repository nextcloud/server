<?php
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

	public function testAppstoreDisabled() {
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

	public function testNoInternet() {
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
	public function testGetEtag(string|null $expected, bool $throws, string $content = '') {
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
