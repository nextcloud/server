<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\PropFindMonitorPlugin;
use OCA\DAV\Connector\Sabre\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;
use Test\TestCase;

class PropFindMonitorPluginTest extends TestCase {

	private PropFindMonitorPlugin $plugin;
	private Server&MockObject $server;
	private LoggerInterface&MockObject $logger;
	private Request&MockObject $request;
	private Response&MockObject $response;

	public static function dataTest(): array {
		$minQueriesTrigger = PropFindMonitorPlugin::THRESHOLD_QUERY_FACTOR
			* PropFindMonitorPlugin::THRESHOLD_NODES;
		return [
			'No queries logged' => [[], 0],
			'Plugins with queries in less than threshold nodes should not be logged' => [
				[
					'propFind' => [
						[
							'PluginName' => [
								'queries' => 100,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES - 1]
						],
						[],
					]
				],
				0
			],
			'Plugins with query-to-node ratio less than threshold should not be logged' => [
				[
					'propFind' => [
						[
							'PluginName' => [
								'queries' => $minQueriesTrigger - 1,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES ],
						],
						[],
					]
				],
				0
			],
			'Plugins with more nodes scanned than queries executed should not be logged' => [
				[
					'propFind' => [
						[
							'PluginName' => [
								'queries' => $minQueriesTrigger,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES * 2],
						],
						[],]
				],
				0
			],
			'Plugins with queries only in highest depth level should not be logged' => [
				[
					'propFind' => [
						[
							'PluginName' => [
								'queries' => $minQueriesTrigger,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES - 1
							]
						],
						[
							'PluginName' => [
								'queries' => $minQueriesTrigger * 2,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES
							]
						],
					]
				],
				0
			],
			'Plugins with too many queries should be logged' => [
				[
					'propFind' => [
						[
							'FirstPlugin' => [
								'queries' => $minQueriesTrigger,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES,
							],
							'SecondPlugin' => [
								'queries' => $minQueriesTrigger,
								'nodes' => PropFindMonitorPlugin::THRESHOLD_NODES,
							]
						],
						[],
					]
				],
				2
			]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTest')]
	public function test(array $queries, $expectedLogCalls): void {
		$this->plugin->initialize($this->server);
		$this->server->expects($this->once())->method('getPluginQueries')
			->willReturn($queries);

		$this->server->expects(empty($queries) ? $this->never() : $this->once())
			->method('getLogger')
			->willReturn($this->logger);

		$this->logger->expects($this->exactly($expectedLogCalls))->method('error');
		$this->plugin->afterResponse($this->request, $this->response);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->plugin = new PropFindMonitorPlugin();
		$this->server = $this->createMock(Server::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(Request::class);
		$this->response = $this->createMock(Response::class);
	}
}
