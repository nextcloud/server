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
