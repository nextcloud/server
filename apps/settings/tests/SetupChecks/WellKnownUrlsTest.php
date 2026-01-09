<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\SetupChecks;

use OCA\Settings\SetupChecks\WellKnownUrls;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class WellKnownUrlsTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;
	private IClientService&MockObject $clientService;
	private LoggerInterface&MockObject $logger;
	private WellKnownUrls&MockObject $setupcheck;

	protected function setUp(): void {
		parent::setUp();

		/** @var IL10N&MockObject */
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message, array $replace) {
				return vsprintf($message, $replace);
			});

		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->setupcheck = $this->getMockBuilder(WellKnownUrls::class)
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

	/**
	 * Test that the SetupCheck is skipped if the system config is set
	 */
	public function testDisabled(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('check_for_working_wellknown_setup')
			->willReturn(false);

		$this->setupcheck
			->expects($this->never())
			->method('runRequest');

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::INFO, $result->getSeverity());
		$this->assertMatchesRegularExpression('/check was skipped/', $result->getDescription());
	}

	/**
	 * Test what happens if the local server could not be reached (no response from the requests)
	 */
	public function testNoResponse(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('check_for_working_wellknown_setup')
			->willReturn(true);

		$this->setupcheck
			->expects($this->once())
			->method('runRequest')
			->will($this->generate([]));

		$result = $this->setupcheck->run();
		$this->assertEquals(SetupResult::INFO, $result->getSeverity());
		$this->assertMatchesRegularExpression('/^Could not check/', $result->getDescription());
	}

	/**
	 * Test responses
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestResponses')]
	public function testResponses($responses, string $expectedSeverity): void {
		$createResponse = function (int $statuscode, array $header = []): IResponse&MockObject {
			$response = $this->createMock(IResponse::class);
			$response->expects($this->any())
				->method('getStatusCode')
				->willReturn($statuscode);
			$response->expects($this->any())
				->method('getHeader')
				->willReturnCallback(fn ($name) => $header[$name] ?? '');
			return $response;
		};

		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('check_for_working_wellknown_setup')
			->willReturn(true);

		/* Use generate to mock a Generator, and $createResponse to create the response objects */
		$responses = array_map(
			fn (array $items) => $this->generate(
				array_map(
					fn (array $item) => $createResponse(...$item),
					$items,
				)
			),
			$responses,
		);

		$this->setupcheck
			->expects($this->atLeastOnce())
			->method('runRequest')
			->willReturnOnConsecutiveCalls(...$responses);

		$result = $this->setupcheck->run();
		$this->assertEquals($expectedSeverity, $result->getSeverity());
	}

	public static function dataTestResponses(): array {
		$wellKnownHeader = ['X-NEXTCLOUD-WELL-KNOWN' => 'yes'];

		return [
			'expected codes' => [
				[
					[[200, $wellKnownHeader]],
					[[200, $wellKnownHeader]],
					[[207]],
					[[207]],
				],
				SetupResult::SUCCESS,
			],
			'late response with expected codes' => [
				[
					[[404], [200, $wellKnownHeader]],
					[[404], [200, $wellKnownHeader]],
					[[404], [207]],
					[[404], [207]],
				],
				SetupResult::SUCCESS,
			],
			'working but disabled webfinger' => [
				[
					[[404, $wellKnownHeader]],
					[[404, $wellKnownHeader]],
					[[207]],
					[[207]],
				],
				SetupResult::SUCCESS,
			],
			'unauthorized webdav but with correct configured redirect' => [
				[
					[[404, $wellKnownHeader]],
					[[404, $wellKnownHeader]],
					[[401, ['X-Guzzle-Redirect-History' => 'https://example.com,https://example.com/remote.php/dav/']]],
					[[401, ['X-Guzzle-Redirect-History' => 'https://example.com/remote.php/dav/']]],
				],
				SetupResult::SUCCESS,
			],
			'not configured path' => [
				[
					[[404]],
					[[404]],
					[[404]],
					[[404]],
				],
				SetupResult::WARNING,
			],
			'Invalid webfinger' => [
				[
					[[404]],
					[[404, $wellKnownHeader]],
					[[207]],
					[[207]],
				],
				SetupResult::WARNING,
			],
			'Invalid nodeinfo' => [
				[
					[[404, $wellKnownHeader]],
					[[404]],
					[[207]],
					[[207]],
				],
				SetupResult::WARNING,
			],
			'Invalid caldav' => [
				[
					[[404, $wellKnownHeader]],
					[[404, $wellKnownHeader]],
					[[404]],
					[[207]],
				],
				SetupResult::WARNING,
			],
		];
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
