<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\OcxProviders;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OcxProvicersTest extends TestCase {
	private IL10N|MockObject $l10n;
	private IConfig|MockObject $config;
	private IURLGenerator|MockObject $urlGenerator;
	private IClientService|MockObject $clientService;
	private LoggerInterface|MockObject $logger;
	private OcxProviders|MockObject $setupcheck;

	protected function setUp(): void {
		parent::setUp();

		/** @var IL10N|MockObject */
		$this->l10n = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});

		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->setupcheck = $this->getMockBuilder(OcxProviders::class)
			->onlyMethods(['runRequest'])
			->setConstructorArgs([
				$this->l10n,
				$this->config,
				$this->urlGenerator,
				$this->clientService,
				$this->logger,
			])
			->getMock();
	}

	public function testSuccess(): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->any())->method('getStatusCode')->willReturn(200);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response]), $this->generate([$response]));

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public function testLateSuccess(): void {
		$response1 = $this->createMock(IResponse::class);
		$response1->expects($this->exactly(3))->method('getStatusCode')->willReturnOnConsecutiveCalls(404, 500, 200);
		$response2 = $this->createMock(IResponse::class);
		$response2->expects($this->any())->method('getStatusCode')->willReturnOnConsecutiveCalls(200);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response1, $response1, $response1]), $this->generate([$response2])); // only one response out of two

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public function testNoResponse(): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->any())->method('getStatusCode')->willReturn(200);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([]), $this->generate([])); // No responses

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Could not check/', $result->getDescription());
	}

	public function testPartialResponse(): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->any())->method('getStatusCode')->willReturn(200);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response]), $this->generate([])); // only one response out of two

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Could not check/', $result->getDescription());
	}

	public function testInvalidResponse(): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->any())->method('getStatusCode')->willReturn(404);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response]), $this->generate([$response])); // only one response out of two

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Your web server is not properly set up/', $result->getDescription());
	}

	public function testPartialInvalidResponse(): void {
		$response1 = $this->createMock(IResponse::class);
		$response1->expects($this->any())->method('getStatusCode')->willReturnOnConsecutiveCalls(200);
		$response2 = $this->createMock(IResponse::class);
		$response2->expects($this->any())->method('getStatusCode')->willReturnOnConsecutiveCalls(404);

		$this->setupcheck
			->expects($this->exactly(2))
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response1]), $this->generate([$response2]));

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Your web server is not properly set up/', $result->getDescription());
	}

	/**
	 * Helper function creates a nicer interface for mocking Generator behavior
	 */
	protected function generate(array $yield_values) {
		return $this->returnCallback(function () use ($yield_values) {
			yield from $yield_values;
		});
	}
}
