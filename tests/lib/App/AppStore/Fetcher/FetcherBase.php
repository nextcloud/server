<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\App\AppStore\Fetcher;

use OC\App\AppStore\Fetcher\Fetcher;
use OC\Files\AppData\AppData;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use Psr\Log\LoggerInterface;
use Test\TestCase;

abstract class FetcherBase extends TestCase {
	/** @var Factory|\PHPUnit\Framework\MockObject\MockObject */
	protected $appDataFactory;
	/** @var IAppData|\PHPUnit\Framework\MockObject\MockObject */
	protected $appData;
	/** @var IClientService|\PHPUnit\Framework\MockObject\MockObject */
	protected $clientService;
	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $timeFactory;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var IRegistry|\PHPUnit\Framework\MockObject\MockObject */
	protected $registry;
	/** @var Fetcher */
	protected $fetcher;
	/** @var string */
	protected $fileName;
	/** @var string */
	protected $endpoint;

	protected function setUp(): void {
		parent::setUp();
		$this->appDataFactory = $this->createMock(Factory::class);
		$this->appData = $this->createMock(AppData::class);
		$this->appDataFactory->expects($this->once())
			->method('get')
			->with('appstore')
			->willReturn($this->appData);
		$this->clientService = $this->createMock(IClientService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->registry = $this->createMock(IRegistry::class);
	}

	public function testGetWithAlreadyExistingFileAndUpToDateTimestampAndVersion() {
		$this->config
			->expects($this->exactly(1))
			->method('getSystemValueString')
			->with($this->equalTo('version'), $this->anything())
			->willReturn('11.0.0.2');
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$file
			->expects($this->once())
			->method('getContent')
			->willReturn('{"timestamp":1200,"data":[{"id":"MyApp"}],"ncversion":"11.0.0.2"}');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(1499);

		$expected = [
			[
				'id' => 'MyApp',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetWithNotExistingFileAndUpToDateTimestampAndVersion() {
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'appstoreurl') {
					return 'https://apps.nextcloud.com/api/v1';
				} elseif ($var === 'version') {
					return '11.0.0.2';
				}
				return $default;
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willThrowException(new NotFoundException());
		$folder
			->expects($this->once())
			->method('newFile')
			->with($this->fileName)
			->willReturn($file);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint)
			->willReturn($response);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp", "foo": "foo"}, {"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"myETag"');
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1502,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->once())
			->method('getContent')
			->willReturn($fileData);
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(1502);

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetWithAlreadyExistingFileAndOutdatedTimestamp() {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'version') {
					return '11.0.0.2';
				} else {
					return $default;
				}
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1502,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				'{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}},"ncversion":"11.0.0.2"}',
				$fileData
			);
		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(
				4801,
				1502
			);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint)
			->willReturn($response);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp", "foo": "foo"}, {"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"myETag"');

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetWithAlreadyExistingFileAndNoVersion() {
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'appstoreurl') {
					return 'https://apps.nextcloud.com/api/v1';
				} elseif ($var === 'version') {
					return '11.0.0.2';
				}
				return $default;
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1201,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				'{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}}',
				$fileData
			);
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(1201);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint)
			->willReturn($response);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp", "foo": "foo"}, {"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"myETag"');

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetWithAlreadyExistingFileAndOutdatedVersion() {
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'appstoreurl') {
					return 'https://apps.nextcloud.com/api/v1';
				} elseif ($var === 'version') {
					return '11.0.0.2';
				}
				return $default;
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1201,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				'{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}},"ncversion":"11.0.0.1"',
				$fileData
			);
		$this->timeFactory
			->method('getTime')
			->willReturn(1201);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint)
			->willReturn($response);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp", "foo": "foo"}, {"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"myETag"');

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetWithExceptionInClient() {
		$this->config->method('getSystemValueString')
			->willReturnArgument(1);
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$file
			->expects($this->once())
			->method('getContent')
			->willReturn('{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}}}');
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint)
			->willThrowException(new \Exception());

		$this->assertSame([], $this->fetcher->get());
	}

	public function testGetMatchingETag() {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'version') {
					return '11.0.0.2';
				} else {
					return $default;
				}
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$origData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1200,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';

		$newData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":4802,"ncversion":"11.0.0.2","ETag":"\"myETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($newData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				$origData,
				$newData,
			);
		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(
				4801,
				4802
			);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with(
				$this->equalTo($this->endpoint),
				$this->equalTo([
					'timeout' => 60,
					'headers' => [
						'If-None-Match' => '"myETag"'
					]
				])
			)->willReturn($response);
		$response->method('getStatusCode')
			->willReturn(304);

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];

		$this->assertSame($expected, $this->fetcher->get());
	}

	public function testGetNoMatchingETag() {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'version') {
					return '11.0.0.2';
				} else {
					return $default;
				}
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":4802,"ncversion":"11.0.0.2","ETag":"\"newETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				'{"data":[{"id":"MyOldApp","abc":"def"}],"timestamp":1200,"ncversion":"11.0.0.2","ETag":"\"myETag\""}',
				$fileData,
			);
		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(
				4801,
				4802,
			);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with(
				$this->equalTo($this->endpoint),
				$this->equalTo([
					'timeout' => 60,
					'headers' => [
						'If-None-Match' => '"myETag"',
					]
				])
			)
			->willReturn($response);
		$response->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"newETag"');

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}


	public function testFetchAfterUpgradeNoETag() {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function ($key, $default) {
				if ($key === 'version') {
					return '11.0.0.3';
				} else {
					return $default;
				}
			});
		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($file);
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1501,"ncversion":"11.0.0.3","ETag":"\"newETag\""}';
		$file
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls(
				'{"data":[{"id":"MyOldApp","abc":"def"}],"timestamp":1200,"ncversion":"11.0.0.2","ETag":"\"myETag\""}',
				$fileData
			);
		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->once())
			->method('get')
			->with(
				$this->equalTo($this->endpoint),
				$this->equalTo([
					'timeout' => 60,
				])
			)
			->willReturn($response);
		$response->method('getStatusCode')
			->willReturn(200);
		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}]');
		$response->method('getHeader')
			->with($this->equalTo('ETag'))
			->willReturn('"newETag"');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(1501);

		$expected = [
			[
				'id' => 'MyNewApp',
				'foo' => 'foo',
			],
			[
				'id' => 'bar',
			],
		];
		$this->assertSame($expected, $this->fetcher->get());
	}
}
