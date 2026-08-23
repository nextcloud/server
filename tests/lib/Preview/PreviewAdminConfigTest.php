<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\HEIC;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\JPEG;
use OC\Preview\Movie;
use OC\Preview\MSOfficeDoc;
use OC\Preview\PDF;
use OC\Preview\PNG;
use OC\Preview\PreviewAdminConfig;
use OCP\IAppConfig;
use OCP\IBinaryFinder;
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
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

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

	public function testSetRetentionAndConcurrency(): void {
		$calls = [];
		$this->config->method('setSystemValue')->willReturnCallback(function (string $key, mixed $value) use (&$calls): void {
			$calls[$key] = $value;
		});
		$this->adminConfig->setSettings([
			'previewExpirationDays' => 90,
			'failuresRetentionDays' => 14,
			'failuresMaxRows' => 1000,
			'previewConcurrencyNew' => 2,
			'previewConcurrencyAll' => 6,
		]);
		$this->assertSame(90, $calls['preview_expiration_days']);
		$this->assertSame(14, $calls['preview_failures_retention_days']);
		$this->assertSame(1000, $calls['preview_failures_max_rows']);
		$this->assertSame(2, $calls['preview_concurrency_new']);
		$this->assertSame(6, $calls['preview_concurrency_all']);
	}

	public function testEmptyConcurrencyDeletesKey(): void {
		$this->config->expects($this->once())->method('deleteSystemValue')->with('preview_concurrency_new');
		$this->adminConfig->setSettings(['previewConcurrencyNew' => '']);
	}

	public function testWebpQualityIsWritten(): void {
		$this->appConfig->expects($this->once())->method('setValueInt')->with('preview', 'webp_quality', 75);
		$this->adminConfig->setSettings(['webpQuality' => 75]);
	}

	public function testProviderAvailabilityUsesDetectors(): void {
		$imagick = $this->createMock(IMagickSupport::class);
		$imagick->method('hasExtension')->willReturn(true);
		$imagick->method('supportsFormat')->willReturnCallback(fn (string $format) => $format === 'HEIC');
		$finder = $this->createMock(IBinaryFinder::class);
		$finder->method('findBinaryPath')->willReturnCallback(fn (string $name) => $name === 'ffmpeg' ? '/usr/bin/ffmpeg' : false);

		$this->config->method('getSystemValue')->willReturnCallback(fn (string $key, mixed $default) => $default);
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(fn (string $key, string $default) => $default);
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

		$config = new PreviewAdminConfig($this->config, $this->appConfig, $imagick, $finder);
		$settings = $config->getSettings();
		$byClass = [];
		foreach ($settings['providers'] as $row) {
			$byClass[$row['class']] = $row;
		}

		$this->assertTrue($settings['detection']['ffmpegFound']);
		$this->assertFalse($settings['detection']['officeFound']);
		$this->assertTrue($byClass[HEIC::class]['available']);
		$this->assertFalse($byClass[PDF::class]['available']);
		$this->assertTrue($byClass[Movie::class]['available']);
		$this->assertFalse($byClass[MSOfficeDoc::class]['available']);
		$this->assertFalse($byClass[Imaginary::class]['available']);
		$this->assertTrue($byClass[JPEG::class]['available']);
	}
}
