<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Controllers;

use OCA\Files_Sharing\Controller\ExternalSharesController;
use OCA\Files_Sharing\External\Manager;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ExternalShareControllerTest
 *
 * @package OCA\Files_Sharing\Controllers
 */
class ExternalShareControllerTest extends \Test\TestCase {
	/** @var IRequest */
	private $request;
	/** @var \OCA\Files_Sharing\External\Manager */
	private $externalManager;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IClientService */
	private $clientService;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->externalManager = $this->createMock(Manager::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->config = $this->createMock(IConfig::class);
	}

	/**
	 * @return ExternalSharesController
	 */
	public function getExternalShareController() {
		return new ExternalSharesController(
			'files_sharing',
			$this->request,
			$this->externalManager,
			$this->clientService,
			$this->config,
		);
	}

	public function testIndex(): void {
		$this->externalManager
			->expects($this->once())
			->method('getOpenShares')
			->willReturn(['MyDummyArray']);

		$this->assertEquals(new JSONResponse(['MyDummyArray']), $this->getExternalShareController()->index());
	}

	public function testCreate(): void {
		$this->externalManager
			->expects($this->once())
			->method('acceptShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->create(4));
	}

	public function testDestroy(): void {
		$this->externalManager
			->expects($this->once())
			->method('declineShare')
			->with(4);

		$this->assertEquals(new JSONResponse(), $this->getExternalShareController()->destroy(4));
	}

	public function testRemoteWithValidHttps(): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$response
			->expects($this->exactly(2))
			->method('getBody')
			->willReturnOnConsecutiveCalls(
				'Certainly not a JSON string',
				'{"installed":true,"maintenance":false,"version":"8.1.0.8","versionstring":"8.1.0","edition":""}'
			);
		$client
			->expects($this->any())
			->method('get')
			->willReturn($response);

		$this->clientService
			->expects($this->exactly(2))
			->method('newClient')
			->willReturn($client);

		$this->assertEquals(new DataResponse('https'), $this->getExternalShareController()->testRemote('nextcloud.com'));
	}

	public function testRemoteWithWorkingHttp(): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$client
			->method('get')
			->willReturn($response);
		$response
			->expects($this->exactly(5))
			->method('getBody')
			->willReturnOnConsecutiveCalls(
				'Certainly not a JSON string',
				'Certainly not a JSON string',
				'Certainly not a JSON string',
				'Certainly not a JSON string',
				'{"installed":true,"maintenance":false,"version":"8.1.0.8","versionstring":"8.1.0","edition":""}'
			);
		$this->clientService
			->expects($this->exactly(5))
			->method('newClient')
			->willReturn($client);

		$this->assertEquals(new DataResponse('http'), $this->getExternalShareController()->testRemote('nextcloud.com'));
	}

	public function testRemoteWithInvalidRemote(): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$client
			->expects($this->exactly(6))
			->method('get')
			->willReturn($response);
		$response
			->expects($this->exactly(6))
			->method('getBody')
			->willReturn('Certainly not a JSON string');
		$this->clientService
			->expects($this->exactly(6))
			->method('newClient')
			->willReturn($client);

		$this->assertEquals(new DataResponse(false), $this->getExternalShareController()->testRemote('nextcloud.com'));
	}

	public function dataRemoteWithInvalidRemoteURLs(): array {
		return [
			['nextcloud.com?query'],
			['nextcloud.com/#anchor'],
			['nextcloud.com/;tomcat'],
		];
	}

	/**
	 * @dataProvider dataRemoteWithInvalidRemoteURLs
	 * @param string $remote
	 */
	public function testRemoteWithInvalidRemoteURLs(string $remote): void {
		$this->clientService
			->expects($this->never())
			->method('newClient');

		$this->assertEquals(new DataResponse(false), $this->getExternalShareController()->testRemote($remote));
	}
}
