<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests;

use OCA\Settings\SetupChecks\DataDirectoryProtected;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class DataDirectoryProtectedTest extends TestCase {
	private IL10N|MockObject $l10n;
	private IConfig|MockObject $config;
	private IURLGenerator|MockObject $urlGenerator;
	private IClientService|MockObject $clientService;
	private LoggerInterface|MockObject $logger;
	private DataDirectoryProtected|MockObject $setupcheck;

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

		$this->setupcheck = $this->getMockBuilder(DataDirectoryProtected::class)
			->onlyMethods(['runHEAD'])
			->setConstructorArgs([
				$this->l10n,
				$this->config,
				$this->urlGenerator,
				$this->clientService,
				$this->logger,
			])
			->getMock();
	}

	/**
	 * @dataProvider dataTestStatusCode
	 */
	public function testStatusCode(array $status, string $expected): void {
		$responses = array_map(function ($state) {
			$response = $this->createMock(IResponse::class);
			$response->expects($this->any())->method('getStatusCode')->willReturn($state);
			return $response;
		}, $status);

		$this->setupcheck
			->expects($this->once())
			->method('runHEAD')
			->will($this->generate($responses));

		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->willReturn('');

		$result = $this->setupcheck->run();
		$this->assertEquals($expected, $result->getSeverity());
	}

	public function dataTestStatusCode(): array {
		return [
			'success: forbidden access' => [[403], SetupResult::SUCCESS],
			'error: can access' => [[200], SetupResult::ERROR],
			'error: one forbidden one can access' => [[403, 200], SetupResult::ERROR],
			'warning: connection issue' => [[], SetupResult::WARNING],
		];
	}

	public function testNoResponse(): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->any())->method('getStatusCode')->willReturn(200);

		$this->setupcheck
			->expects($this->once())
			->method('runHEAD')
			->will($this->generate([]));

		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->willReturn('');

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Could not check/', $result->getDescription());
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
