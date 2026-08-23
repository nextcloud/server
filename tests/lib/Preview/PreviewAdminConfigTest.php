<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\JPEG;
use OC\Preview\PNG;
use OC\Preview\PreviewAdminConfig;
use OCP\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewAdminConfigTest extends TestCase {
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private PreviewAdminConfig $adminConfig;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->adminConfig = new PreviewAdminConfig($this->config, $this->appConfig);
	}

	public function testGetSettingsUsesDefaultsWhenKeysMissing(): void {
		$this->config->method('getSystemValue')->willReturnCallback(fn (string $key, mixed $default) => $default);
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(fn (string $key, string $default) => $default);
		$this->appConfig->method('getValueInt')->with('preview', 'jpeg_quality', 80)->willReturn(80);

		$settings = $this->adminConfig->getSettings();
		$this->assertTrue($settings['enablePreviews']);
		$this->assertSame(4096, $settings['previewMaxX']);
		$this->assertSame(PreviewAdminConfig::getDefaultEnabledProviders(), $settings['defaultEnabledProviders']);
		$this->assertNotEmpty($settings['providers']);
		$this->assertSame('private', $settings['cacheAuthenticated']['visibility']);
		$this->assertTrue($settings['cacheAuthenticated']['immutable']);
	}

	public function testSetEnabledProvidersPreservesOrder(): void {
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('enabledPreviewProviders', [JPEG::class, PNG::class]);

		$result = $this->adminConfig->setEnabledProviders([JPEG::class, PNG::class, JPEG::class]);
		$this->assertSame([JPEG::class, PNG::class], $result);
	}

	public function testSetEnabledProvidersRejectsEmptyList(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->adminConfig->setEnabledProviders([]);
	}

	public function testSetEnabledProvidersAllowsEmptyWithConfirmation(): void {
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('enabledPreviewProviders', []);
		$this->adminConfig->setEnabledProviders([], true);
	}

	public function testMimeMapRoundTrip(): void {
		$map = [
			'image/heic' => ['OC\\Preview\\Imaginary', 'OC\\Preview\\HEIC'],
		];
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('preview_provider_mime_priority', $map);

		$this->adminConfig->setSettings(['mimePriority' => $map]);
	}

	public function testRejectsInvalidImaginaryUrl(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->adminConfig->validateImaginaryUrl('javascript:alert(1)');
	}

	public function testAcceptsEmptyImaginaryUrl(): void {
		$this->assertSame('', $this->adminConfig->validateImaginaryUrl('  '));
	}
}
