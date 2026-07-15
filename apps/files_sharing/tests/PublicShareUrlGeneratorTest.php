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
use Test\TestCase;

class PublicShareUrlGeneratorTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IURLGenerator&MockObject $urlGenerator;
	private PublicShareUrlGenerator $publicShareUrlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->publicShareUrlGenerator = new PublicShareUrlGenerator($this->appConfig, $this->urlGenerator);
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

		self::assertSame('https://cloud.example/s/abc', $this->publicShareUrlGenerator->getUrl('abc'));
	}

	#[DataProvider('validBaseUrlProvider')]
	public function testUsesConfiguredBaseUrl(string $baseUrl, string $expectedUrl): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('core', 'shareapi_public_link_base_url', '')
			->willReturn($baseUrl);
		$this->urlGenerator->expects($this->never())
			->method('linkToRouteAbsolute');

		self::assertSame($expectedUrl, $this->publicShareUrlGenerator->getUrl('abc'));
	}

	public static function validBaseUrlProvider(): array {
		return [
			['https://public-gateway.example', 'https://public-gateway.example/s/abc'],
			['https://public-gateway.example/prefix/', 'https://public-gateway.example/prefix/s/abc'],
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
}
