<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\SetupChecks;

use OCA\Settings\SetupChecks\SecurityHeaders;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class SecurityHeadersTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private IURLGenerator&MockObject $urlGenerator;
	private IClientService&MockObject $clientService;
	private LoggerInterface&MockObject $logger;
	private SecurityHeaders&MockObject $setupcheck;

	protected function setUp(): void {
		parent::setUp();

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

		$this->setupcheck = $this->getMockBuilder(SecurityHeaders::class)
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

	public function testInvalidStatusCode(): void {
		$this->setupResponse(500, []);

		$result = $this->setupcheck->run();
		$this->assertMatchesRegularExpression('/^Could not check that your web server serves security headers correctly/', $result->getDescription());
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
	}

	public function testAllHeadersMissing(): void {
		$this->setupResponse(200, []);

		$result = $this->setupcheck->run();
		$this->assertMatchesRegularExpression('/^Some headers are not set correctly on your instance/', $result->getDescription());
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
	}

	public function testSomeHeadersMissing(): void {
		$this->setupResponse(
			200,
			[
				'X-Robots-Tag' => 'noindex, nofollow',
				'X-Frame-Options' => 'SAMEORIGIN',
				'Strict-Transport-Security' => 'max-age=15768000;preload',
				'X-Permitted-Cross-Domain-Policies' => 'none',
				'Referrer-Policy' => 'no-referrer',
			]
		);

		$result = $this->setupcheck->run();
		$this->assertEquals(
			"Some headers are not set correctly on your instance\n- The `X-Content-Type-Options` HTTP header is not set to `nosniff`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.\n",
			$result->getDescription()
		);
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
	}

	public static function dataSuccess(): array {
		return [
			// description => modifiedHeaders
			'basic' => [[]],
			'no-space-in-x-robots' => [['X-Robots-Tag' => 'noindex,nofollow']],
			'strict-origin-when-cross-origin' => [['Referrer-Policy' => 'strict-origin-when-cross-origin']],
			'referrer-no-referrer-when-downgrade' => [['Referrer-Policy' => 'no-referrer-when-downgrade']],
			'referrer-strict-origin' => [['Referrer-Policy' => 'strict-origin']],
			'referrer-strict-origin-when-cross-origin' => [['Referrer-Policy' => 'strict-origin-when-cross-origin']],
			'referrer-same-origin' => [['Referrer-Policy' => 'same-origin']],
			'hsts-minimum' => [['Strict-Transport-Security' => 'max-age=15552000']],
			'hsts-include-subdomains' => [['Strict-Transport-Security' => 'max-age=99999999; includeSubDomains']],
			'hsts-include-subdomains-preload' => [['Strict-Transport-Security' => 'max-age=99999999; preload; includeSubDomains']],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataSuccess')]
	public function testSuccess(array $headers): void {
		$headers = array_merge(
			[
				'X-Content-Type-Options' => 'nosniff',
				'X-Robots-Tag' => 'noindex, nofollow',
				'X-Frame-Options' => 'SAMEORIGIN',
				'Strict-Transport-Security' => 'max-age=15768000',
				'X-Permitted-Cross-Domain-Policies' => 'none',
				'Referrer-Policy' => 'no-referrer',
			],
			$headers
		);
		$this->setupResponse(
			200,
			$headers
		);

		$result = $this->setupcheck->run();
		$this->assertEquals(
			'Your server is correctly configured to send security headers.',
			$result->getDescription()
		);
		$this->assertEquals(SetupResult::SUCCESS, $result->getSeverity());
	}

	public static function dataFailure(): array {
		return [
			// description => modifiedHeaders
			'x-robots-none' => [['X-Robots-Tag' => 'none'], "- The `X-Robots-Tag` HTTP header is not set to `noindex,nofollow`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.\n"],
			'referrer-origin' => [['Referrer-Policy' => 'origin'], "- The `Referrer-Policy` HTTP header is not set to `no-referrer`, `no-referrer-when-downgrade`, `strict-origin`, `strict-origin-when-cross-origin` or `same-origin`. This can leak referer information. See the {w3c-recommendation}.\n"],
			'referrer-origin-when-cross-origin' => [['Referrer-Policy' => 'origin-when-cross-origin'], "- The `Referrer-Policy` HTTP header is not set to `no-referrer`, `no-referrer-when-downgrade`, `strict-origin`, `strict-origin-when-cross-origin` or `same-origin`. This can leak referer information. See the {w3c-recommendation}.\n"],
			'referrer-unsafe-url' => [['Referrer-Policy' => 'unsafe-url'], "- The `Referrer-Policy` HTTP header is not set to `no-referrer`, `no-referrer-when-downgrade`, `strict-origin`, `strict-origin-when-cross-origin` or `same-origin`. This can leak referer information. See the {w3c-recommendation}.\n"],
			'hsts-missing' => [['Strict-Transport-Security' => ''], "- The `Strict-Transport-Security` HTTP header is not set (should be at least `15552000` seconds). For enhanced security, it is recommended to enable HSTS.\n"],
			'hsts-too-low' => [['Strict-Transport-Security' => 'max-age=15551999'], "- The `Strict-Transport-Security` HTTP header is not set to at least `15552000` seconds (current value: `15551999`). For enhanced security, it is recommended to use a long HSTS policy.\n"],
			'hsts-malformed' => [['Strict-Transport-Security' => 'iAmABogusHeader342'], "- The `Strict-Transport-Security` HTTP header is malformed: `iAmABogusHeader342`. For enhanced security, it is recommended to enable HSTS.\n"],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFailure')]
	public function testFailure(array $headers, string $msg): void {
		$headers = array_merge(
			[
				'X-Content-Type-Options' => 'nosniff',
				'X-Robots-Tag' => 'noindex, nofollow',
				'X-Frame-Options' => 'SAMEORIGIN',
				'Strict-Transport-Security' => 'max-age=15768000',
				'X-Permitted-Cross-Domain-Policies' => 'none',
				'Referrer-Policy' => 'no-referrer',
			],
			$headers
		);
		$this->setupResponse(
			200,
			$headers
		);

		$result = $this->setupcheck->run();
		$this->assertEquals(
			'Some headers are not set correctly on your instance' . "\n$msg",
			$result->getDescription()
		);
		$this->assertEquals(SetupResult::WARNING, $result->getSeverity());
	}

	protected function setupResponse(int $statuscode, array $headers): void {
		$response = $this->createMock(IResponse::class);
		$response->expects($this->atLeastOnce())->method('getStatusCode')->willReturn($statuscode);
		$response->expects($this->any())->method('getHeader')
			->willReturnCallback(
				fn (string $header): string => $headers[$header] ?? ''
			);

		$this->setupcheck
			->expects($this->atLeastOnce())
			->method('runRequest')
			->willReturnOnConsecutiveCalls($this->generate([$response]));
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
