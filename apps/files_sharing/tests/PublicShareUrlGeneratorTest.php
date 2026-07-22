<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\PublicShareUrlGenerator;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class PublicShareUrlGeneratorTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IURLGenerator&MockObject $urlGenerator;
	private LoggerInterface&MockObject $logger;
	private PublicShareUrlGenerator $publicShareUrlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->publicShareUrlGenerator = new PublicShareUrlGenerator($this->appConfig, $this->urlGenerator, $this->logger);
	}

	public function testUsesExistingRouteWhenBaseUrlIsUnset(): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('core', 'shareapi_public_link_base_url', '')
			->willReturn('');
		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'abc'])
			->willReturn('https://cloud.example/s/abc');
		$this->urlGenerator->expects($this->never())
			->method('linkToRoute');
		$this->logger->expects($this->never())
			->method('warning');

		self::assertSame('https://cloud.example/s/abc', $this->publicShareUrlGenerator->getUrl('abc'));
	}

	#[DataProvider('validBaseUrlProvider')]
	public function testUsesConfiguredBaseUrl(string $baseUrl, string $routePath, string $webroot, string $expectedUrl): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('core', 'shareapi_public_link_base_url', '')
			->willReturn($baseUrl);
		$this->urlGenerator->expects($this->never())
			->method('linkToRouteAbsolute');
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'abc'])
			->willReturn($routePath);
		$this->urlGenerator->method('getWebroot')
			->willReturn($webroot);
		$this->logger->expects($this->never())
			->method('warning');

		self::assertSame($expectedUrl, $this->publicShareUrlGenerator->getUrl('abc'));
	}

	public static function validBaseUrlProvider(): array {
		return [
			'no path' => [
				'https://public-gateway.example', '/s/abc', '',
				'https://public-gateway.example/s/abc',
			],
			'path prefix with trailing slash' => [
				'https://public-gateway.example/prefix/', '/s/abc', '',
				'https://public-gateway.example/prefix/s/abc',
			],
			'path prefix without trailing slash' => [
				'https://public-gateway.example/prefix', '/s/abc', '',
				'https://public-gateway.example/prefix/s/abc',
			],
			'custom port' => [
				'https://public-gateway.example:8443', '/s/abc', '',
				'https://public-gateway.example:8443/s/abc',
			],
			'surrounding whitespace is trimmed' => [
				'  https://public-gateway.example  ', '/s/abc', '',
				'https://public-gateway.example/s/abc',
			],
			'front controller prefix is preserved' => [
				'https://public-gateway.example', '/index.php/s/abc', '',
				'https://public-gateway.example/index.php/s/abc',
			],
			'instance webroot is replaced by the base url' => [
				'https://public-gateway.example', '/nextcloud/s/abc', '/nextcloud',
				'https://public-gateway.example/s/abc',
			],
		];
	}

	#[DataProvider('invalidBaseUrlProvider')]
	public function testInvalidBaseUrlUsesExistingRoute(string $baseUrl): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('core', 'shareapi_public_link_base_url', '')
			->willReturn($baseUrl);
		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'abc'])
			->willReturn('https://cloud.example/s/abc');
		$this->urlGenerator->expects($this->never())
			->method('linkToRoute');
		$this->logger->expects($this->once())
			->method('warning');

		self::assertSame('https://cloud.example/s/abc', $this->publicShareUrlGenerator->getUrl('abc'));
	}

	public static function invalidBaseUrlProvider(): array {
		return [
			['gateway.example'],
			['ftp://public-gateway.example'],
			['https://user@public-gateway.example'],
			['https://user:password@public-gateway.example'],
			['https://public-gateway.example/?query=value'],
			['https://public-gateway.example/#fragment'],
		];
	}

	public function testInvalidBaseUrlIsOnlyReportedOnce(): void {
		$this->appConfig->method('getValueString')
			->willReturn('gateway.example');
		$this->urlGenerator->method('linkToRouteAbsolute')
			->willReturn('https://cloud.example/s/abc');
		$this->logger->expects($this->once())
			->method('warning');

		$this->publicShareUrlGenerator->getUrl('abc');
		$this->publicShareUrlGenerator->getUrl('abc');
	}
}
