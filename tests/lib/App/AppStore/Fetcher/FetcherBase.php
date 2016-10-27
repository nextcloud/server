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

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\Fetcher;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use Test\TestCase;

abstract class FetcherBase extends TestCase {
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	protected $appData;
	/** @var IClientService|\PHPUnit_Framework_MockObject_MockObject */
	protected $clientService;
	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $timeFactory;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var Fetcher */
	protected $fetcher;
	/** @var string */
	protected $fileName;
	/** @var string */
	protected $endpoint;

	public function setUp() {
		parent::setUp();
		$this->appData = $this->createMock(IAppData::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
	}

	public function testGetWithAlreadyExistingFileAndUpToDateTimestamp() {
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
			->willReturn('{"timestamp":1200,"data":[{"id":"MyApp"}]}');
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

	public function testGetWithNotExistingFileAndUpToDateTimestamp() {
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('/')
			->willReturn($folder);
		$folder
			->expects($this->at(0))
			->method('getFile')
			->with($this->fileName)
			->willThrowException(new NotFoundException());
		$folder
			->expects($this->at(1))
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
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1502}';
		$file
			->expects($this->at(0))
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->at(1))
			->method('getContent')
			->willReturn($fileData);
		$this->timeFactory
			->expects($this->at(0))
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
			->expects($this->at(0))
			->method('getContent')
			->willReturn('{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}}}');
		$this->timeFactory
			->expects($this->at(0))
			->method('getTime')
			->willReturn(1501);
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
		$fileData = '{"data":[{"id":"MyNewApp","foo":"foo"},{"id":"bar"}],"timestamp":1502}';
		$file
			->expects($this->at(1))
			->method('putContent')
			->with($fileData);
		$file
			->expects($this->at(2))
			->method('getContent')
			->willReturn($fileData);
		$this->timeFactory
			->expects($this->at(1))
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

	public function testGetWithExceptionInClient() {
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
			->expects($this->at(0))
			->method('getContent')
			->willReturn('{"timestamp":1200,"data":{"MyApp":{"id":"MyApp"}}}');
		$this->timeFactory
			->expects($this->at(0))
			->method('getTime')
			->willReturn(1501);
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
}
