<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Updater;

use OC\Updater\ReleaseMetadata;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;

class ReleaseMetadataTest extends \Test\TestCase {
	private IClientService|MockObject $clientService;

	protected function setUp(): void {
		parent::setUp();
		$this->clientService = $this->getMockBuilder(IClientService::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function testDownloadMetadata(): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->willReturn($client);
		$client->expects($this->once())
			->method('get')
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->with()
			->willReturn($this->resultRequest());


		$releaseMetadata = new ReleaseMetadata($this->clientService);
		$this->assertSame($this->resultRequestArray(), $releaseMetadata->downloadMetadata('ouila'));
	}

	/**
	 * @dataProvider getMetadataUrlProvider
	 *
	 * @param string $version
	 * @param string $url
	 */
	public function testGetMetadata(string $version, string $url): void {
		$client = $this->createMock(IClient::class);
		$response = $this->createMock(IResponse::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->with()
			->willReturn($client);
		$client->expects($this->once())
			->method('get')
			->with($url)
			->willReturn($response);

		$response->expects($this->once())
			->method('getBody')
			->with()
			->willReturn('{}');

		$releaseMetadata = new ReleaseMetadata($this->clientService);
		$releaseMetadata->getMetadata($version);
	}

	/**
	 * @return array
	 */
	public function getMetadataUrlProvider(): array {
		return [
			[
				'30.0.0',
				'https://download.nextcloud.com/server/releases/nextcloud-30.0.0.metadata'
			],
			[
				'30.0.0-beta1',
				'https://download.nextcloud.com/server/prereleases/nextcloud-30.0.0-beta1.metadata'
			],
			[
				'30',
				'https://download.nextcloud.com/server/releases/latest-30.metadata'
			]
		];
	}

	private function resultRequest(): string {
		return json_encode($this->resultRequestArray());
	}

	private function resultRequestArray(): array {
		return [
			'migrations' => [
				'core' => [],
				'apps' => [
					'testing' => [
						'30000Date20240102030405' => [
							'class' => 'OCP\\Migration\\Attributes\\DropTable',
							'table' => 'old_table',
							'description' => '',
							'notes' => [],
							'columns' => []
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\CreateTable',
							'table' => 'new_table',
							'description' => 'Table is used to store things, but also to get more things',
							'notes' => [
								'this is a notice',
								'and another one, if really needed'
							],
							'columns' => []
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\AddColumn',
							'table' => 'my_table',
							'description' => '',
							'notes' => [],
							'name' => '',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\AddColumn',
							'table' => 'my_table',
							'description' => '',
							'notes' => [],
							'name' => 'another_field',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\AddColumn',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'name' => 'last_one',
							'type' => 'date'
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\AddIndex',
							'table' => 'my_table',
							'description' => '',
							'notes' => [],
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\AddIndex',
							'table' => 'my_table',
							'description' => '',
							'notes' => [],
							'type' => 'primary'
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\DropColumn',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'name' => '',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\DropColumn',
							'table' => 'other_table',
							'description' => 'field is not used anymore and replaced by \'last_one\'',
							'notes' => [],
							'name' => 'old_column',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\DropIndex',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'name' => '',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'name' => 'this_field',
							'type' => ''
						],
						[
							'class' => 'OCP\\Migration\\Attributes\\ModifyColumn',
							'table' => 'other_table',
							'description' => '',
							'notes' => [],
							'name' => 'this_field',
							'type' => 'bigint'
						]
					]
				]
			]
		];
	}
}
