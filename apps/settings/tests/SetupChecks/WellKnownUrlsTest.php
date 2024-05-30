<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Tests;

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
	private IL10N|MockObject $l10n;
	private IConfig|MockObject $config;
	private IURLGenerator|MockObject $urlGenerator;
	private IClientService|MockObject $clientService;
	private LoggerInterface|MockObject $logger;
	private WellKnownUrls|MockObject $setupcheck;

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
	 * @dataProvider dataTestResponses
	 */
	public function testResponses($responses, string $expectedSeverity): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('check_for_working_wellknown_setup')
			->willReturn(true);

		$this->setupcheck
			->expects($this->atLeastOnce())
			->method('runRequest')
			->willReturnOnConsecutiveCalls(...$responses);

		$result = $this->setupcheck->run();
		$this->assertEquals($expectedSeverity, $result->getSeverity());
	}

	public function dataTestResponses(): array {
		$createResponse = function (int $statuscode, array $header = []): IResponse|MockObject {
			$response = $this->createMock(IResponse::class);
			$response->expects($this->any())
				->method('getStatusCode')
				->willReturn($statuscode);
			$response->expects($this->any())
				->method('getHeader')
				->willReturnCallback(fn ($name) => $header[$name] ?? '');
			return $response;
		};

		$wellKnownHeader = ['X-NEXTCLOUD-WELL-KNOWN' => 'yes'];

		return [
			'expected codes' => [
				[
					$this->generate([$createResponse(200, $wellKnownHeader)]),
					$this->generate([$createResponse(200, $wellKnownHeader)]),
					$this->generate([$createResponse(207)]),
					$this->generate([$createResponse(207)]),
				],
				SetupResult::SUCCESS,
			],
			'late response with expected codes' => [
				[
					$this->generate([$createResponse(404), $createResponse(200, $wellKnownHeader)]),
					$this->generate([$createResponse(404), $createResponse(200, $wellKnownHeader)]),
					$this->generate([$createResponse(404), $createResponse(207)]),
					$this->generate([$createResponse(404), $createResponse(207)]),
				],
				SetupResult::SUCCESS,
			],
			'working but disabled webfinger' => [
				[
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(207)]),
					$this->generate([$createResponse(207)]),
				],
				SetupResult::SUCCESS,
			],
			'unauthorized webdav but with correct configured redirect' => [
				[
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(401, ['X-Guzzle-Redirect-History' => 'https://example.com,https://example.com/remote.php/dav/'])]),
					$this->generate([$createResponse(401, ['X-Guzzle-Redirect-History' => 'https://example.com/remote.php/dav/'])]),
				],
				SetupResult::SUCCESS,
			],
			'not configured path' => [
				[
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(404)]),
				],
				SetupResult::WARNING,
			],
			'Invalid webfinger' => [
				[
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(207)]),
					$this->generate([$createResponse(207)]),
				],
				SetupResult::WARNING,
			],
			'Invalid nodeinfo' => [
				[
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(207)]),
					$this->generate([$createResponse(207)]),
				],
				SetupResult::WARNING,
			],
			'Invalid caldav' => [
				[
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(404, $wellKnownHeader)]),
					$this->generate([$createResponse(404)]),
					$this->generate([$createResponse(207)]),
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
