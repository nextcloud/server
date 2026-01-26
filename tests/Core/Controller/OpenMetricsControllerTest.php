<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Controller;

use OC\Core\Controller\OpenMetricsController;
use OC\OpenMetrics\ExporterManager;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamTraversableResponse;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OpenMetricsControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IConfig&MockObject $config;
	private ExporterManager&MockObject $exporterManager;
	private LoggerInterface&MockObject $logger;
	private OpenMetricsController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->request->method('getRemoteAddress')
			->willReturn('192.168.1.1');
		$this->config = $this->createMock(IConfig::class);
		$this->exporterManager = $this->createMock(ExporterManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->controller = new OpenMetricsController('core', $this->request, $this->config, $this->exporterManager, $this->logger);
	}

	public function testGetMetrics(): void {
		$output = $this->createMock(IOutput::class);
		$fullOutput = '';
		$output->method('setOutput')
			->willReturnCallback(function ($output) use (&$fullOutput) {
				$fullOutput .= $output;
			});
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('openmetrics_allowed_clients')
			->willReturn(['192.168.0.0/16']);
		$response = $this->controller->export();
		$this->assertInstanceOf(StreamTraversableResponse::class, $response);
		$this->assertEquals('200', $response->getStatus());
		$this->assertEquals('application/openmetrics-text; version=1.0.0; charset=utf-8', $response->getHeaders()['Content-Type']);
		$expected = <<<EXPECTED
			# TYPE nextcloud_exporter_duration gauge
			# UNIT nextcloud_exporter_duration seconds
			# HELP nextcloud_exporter_duration Exporter run time
			nextcloud_exporter_duration %f
			# EOF

			EXPECTED;
		$response->callback($output);
		$this->assertStringMatchesFormat($expected, $fullOutput);
	}

	public function testGetMetricsFromForbiddenIp(): void {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('openmetrics_allowed_clients')
			->willReturn(['1.2.3.4']);
		$response = $this->controller->export();
		$this->assertInstanceOf(Response::class, $response);
		$this->assertEquals('403', $response->getStatus());
	}
}
