<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Fetcher;

use OC\App\AppStore\Fetcher\Fetcher;
use OC\Files\AppData\AppData;
use OC\Files\AppData\Factory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\GenericFileException;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

abstract class FetcherBase extends TestCase {
	protected Factory&MockObject $appDataFactory;
	protected IAppData&MockObject $appData;
	protected IClientService&MockObject $clientService;
	protected ITimeFactory&MockObject $timeFactory;
	protected IConfig&MockObject $config;
	protected LoggerInterface&MockObject $logger;
	protected IRegistry&MockObject $registry;
	protected Fetcher $fetcher;
	protected string $fileName;
	protected string $endpoint;

	#[\Override]
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

	public function testGetWithAlreadyExistingFileAndUpToDateTimestampAndVersion(): void {
		$this->config
			->method('getSystemValueString')
			->willReturnCallback(function ($var, $default) {
				if ($var === 'version') {
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

	public function testGetWithNotExistingFileAndUpToDateTimestampAndVersion(): void {
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

	public function testGetWithAlreadyExistingFileAndOutdatedTimestamp(): void {
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

	public function testGetWithAlreadyExistingFileAndNoVersion(): void {
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

	public function testGetWithAlreadyExistingFileAndOutdatedVersion(): void {
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

	public function testGetWithExceptionInClient(): void {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function (string $key, string $default): string {
				if ($key === 'version') {
					return '11.0.0.2';
				}

				return $default;
			});

		$this->config->method('getSystemValueBool')
 			->willReturnArgument(1);

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
			->willReturn(json_encode([
				'timestamp' => 1200,
				'data' => [
					['id' => 'MyApp'],
				],
				'ncversion' => '11.0.0.2',
			]));

		// First call checks whether the cache is fresh; the second call is
		// made by the stale-cache fallback closure.
		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(4801, 4801);

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

		$this->assertSame([['id' => 'MyApp']], $this->fetcher->get());
	}

	public function testGetUsesStaleCacheWithinMaximumStaleAge(): void {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function (string $key, string $default): string {
				if ($key === 'version') {
					return '11.0.0.2';
				}

				return $default;
			});

		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
			->willReturn(json_encode([
				'timestamp' => 1000,
				'data' => [
					['id' => 'MyApp'],
				],
				'ncversion' => '11.0.0.2',
			]));

		$now = 1000 + Fetcher::MAX_STALE_SECONDS - 1;

		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls($now, $now);

		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint, [
				'timeout' => 120,
			])
			->willThrowException(new \Exception('temporary failure'));

		$this->assertSame([['id' => 'MyApp']], $this->fetcher->get());
	}

	public function testGetDoesNotUseStaleCacheOlderThanMaximumStaleAge(): void {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function (string $key, string $default): string {
				if ($key === 'version') {
					return '11.0.0.2';
				}

				return $default;
			});

		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
			->willReturn(json_encode([
				'timestamp' => 1000,
				'data' => [
					['id' => 'MyApp'],
				],
				'ncversion' => '11.0.0.2',
			]));

		$now = 1000 + Fetcher::MAX_STALE_SECONDS + 1;

		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls($now, $now);

		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint, [
				'timeout' => 120,
			])
			->willThrowException(new \Exception('temporary failure'));

		$this->assertSame([], $this->fetcher->get());
	}

	public function testGetUsesStaleCacheDuringFailureCooldown(): void {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function (string $key, string $default): string {
				if ($key === 'version') {
					return '11.0.0.2';
				}

				return $default;
			});

		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$this->config->method('getAppValue')
			->willReturnCallback(function (string $app, string $key, string $default): string {
				if ($key === 'appstore-fetcher-lastFailure') {
					return (string)time();
				}

				return $default;
			});

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
			->willReturn(json_encode([
				'timestamp' => 1000,
				'data' => [
					['id' => 'MyApp'],
				],
				'ncversion' => '11.0.0.2',
			]));

		$now = 1000 + Fetcher::MAX_STALE_SECONDS - 1;

		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls($now, $now);

		$this->clientService
			->expects($this->never())
			->method('newClient');

		$this->assertSame([['id' => 'MyApp']], $this->fetcher->get());
	}

	public function testGetAcceptsValidEmptyRefreshResponse(): void {
		$this->config->method('getSystemValueString')
			->willReturnCallback(function (string $key, string $default): string {
				if ($key === 'version') {
					return '11.0.0.2';
				}

				return $default;
			});

		$this->config->method('getSystemValueBool')
			->willReturnArgument(1);

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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

		$oldData = json_encode([
			'timestamp' => 1000,
			'data' => [
				['id' => 'MyApp'],
			],
			'ncversion' => '11.0.0.2',
		]);

		$newData = json_encode([
			'timestamp' => 2000,
			'data' => [],
			'ncversion' => '11.0.0.2',
			'ETag' => '"newETag"',
		]);

		$file
			->expects($this->exactly(2))
			->method('getContent')
			->willReturnOnConsecutiveCalls($oldData, $newData);

		$file
			->expects($this->once())
			->method('putContent')
			->with($newData);

		$this->timeFactory
			->expects($this->exactly(2))
			->method('getTime')
			->willReturnOnConsecutiveCalls(4801, 2000);

		$client = $this->createMock(IClient::class);
		$this->clientService
			->expects($this->once())
			->method('newClient')
			->willReturn($client);

		$response = $this->createMock(IResponse::class);

		$client
			->expects($this->once())
			->method('get')
			->with($this->endpoint, [
				'timeout' => 120,
			])
			->willReturn($response);

		$response
			->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);

		$response
			->expects($this->once())
			->method('getBody')
			->willReturn('[]');

		$response
			->expects($this->once())
			->method('getHeader')
			->with('ETag')
			->willReturn('"newETag"');

		$this->assertSame([], $this->fetcher->get());
	}

	public function testGetMatchingETag(): void {
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

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
					'timeout' => 120,
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

	public function testGetNoMatchingETag(): void {
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

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
					'timeout' => 120,
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

	public function testFetchAfterUpgradeNoETag(): void {
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

		$this->config->method('getAppValue')
			->willReturnMap([
				['settings', 'appstore-fetcher-lastFailure', '0', '0'],
				['settings', 'appstore-timeout', '120', '120'],
			]);

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
					'timeout' => 120,
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

	public function testGetWithUnreadableCacheFileRecreatesAndFetches(): void {
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
			->willReturn(true);

		$folder = $this->createMock(ISimpleFolder::class);
		$corruptedFile = $this->createMock(ISimpleFile::class);
		$freshFile = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->once())
			->method('getFile')
			->with($this->fileName)
			->willReturn($corruptedFile);
		$corruptedFile
			->expects($this->once())
			->method('getContent')
			->willThrowException(new GenericFileException());
		$corruptedFile
			->expects($this->once())
			->method('delete');
		$folder
			->expects($this->once())
			->method('newFile')
			->with($this->fileName)
			->willReturn($freshFile);

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
		$freshFile
			->expects($this->once())
			->method('putContent')
			->with($fileData);
		$freshFile
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
}
