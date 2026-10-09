<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Service;

use OC\Preview\HEIC;
use OC\Preview\IMagickSupport;
use OC\Preview\JPEG;
use OC\Preview\Movie;
use OC\Preview\MSOfficeDoc;
use OC\Preview\PNG;
use OCA\Settings\Service\PreviewSettingsService;
use OCP\IAppConfig;
use OCP\IBinaryFinder;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PreviewSettingsServiceTest extends TestCase {
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IMagickSupport&MockObject $imagickSupport;
	private IBinaryFinder&MockObject $binaryFinder;
	private PreviewSettingsService $service;

	/** @var array<string, mixed> */
	private array $systemConfig = [];

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->willReturnCallback(fn (string $key, mixed $default) => $this->systemConfig[$key] ?? $default);
		$this->config->method('getSystemValueBool')
			->willReturnCallback(fn (string $key, bool $default) => (bool)($this->systemConfig[$key] ?? $default));
		$this->config->method('getSystemValueString')
			->willReturnCallback(fn (string $key, string $default) => (string)($this->systemConfig[$key] ?? $default));

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->imagickSupport = $this->createMock(IMagickSupport::class);
		$this->binaryFinder = $this->createMock(IBinaryFinder::class);
		$this->binaryFinder->method('findBinaryPath')->willReturn(false);

		$this->service = new PreviewSettingsService($this->config, $this->appConfig, $this->imagickSupport, $this->binaryFinder);
	}

	public function testUnsetValuesAreReportedAsNull(): void {
		$settings = $this->service->getSettings();

		$this->assertTrue($settings['enabled']);
		$this->assertNull($settings['maxX']);
		$this->assertNull($settings['jpegQuality']);
		$this->assertNull($settings['concurrencyNew']);
		$this->assertFalse($settings['providersConfigured']);
	}

	public function testStoredValuesAreReported(): void {
		$this->systemConfig = ['preview_max_x' => 2048, 'enable_previews' => false];
		$this->appConfig->method('hasKey')->willReturn(true);
		$this->appConfig->method('getValueInt')->willReturn(90);

		$settings = $this->service->getSettings();

		$this->assertFalse($settings['enabled']);
		$this->assertSame(2048, $settings['maxX']);
		$this->assertSame(90, $settings['jpegQuality']);
	}

	public function testEnabledProvidersComeFirstInTryOrder(): void {
		$this->systemConfig = ['enabledPreviewProviders' => [JPEG::class, '\\' . PNG::class, 'OCA\\Foo\\Provider']];

		$providers = $this->service->getSettings()['providers'];
		$classes = array_column($providers, 'class');

		$this->assertSame([JPEG::class, PNG::class, 'OCA\\Foo\\Provider'], array_slice($classes, 0, 3));
		$this->assertSame([true, true, true, false], array_column(array_slice($providers, 0, 4), 'enabled'));
		$this->assertSame('Provider', $providers[2]['name']);
		$this->assertCount(1, array_keys($classes, PNG::class, true));
	}

	public function testAvailabilityFollowsDependencies(): void {
		$this->systemConfig = ['preview_ffmpeg_path' => '/usr/bin/ffmpeg'];
		$this->imagickSupport->method('hasExtension')->willReturn(true);
		$this->imagickSupport->method('supportsFormat')->willReturnCallback(fn (string $format) => $format === 'HEIC');

		$settings = $this->service->getSettings();
		$available = array_column($settings['providers'], 'available', 'class');

		$this->assertTrue($available[Movie::class]);
		$this->assertTrue($available[HEIC::class]);
		$this->assertFalse($available[MSOfficeDoc::class]);
		$this->assertSame('/usr/bin/ffmpeg', $settings['dependencies']['ffmpeg']);
		$this->assertNull($settings['dependencies']['office']);
	}

	public function testDefaultValuesRemoveTheKeys(): void {
		$this->config->expects($this->once())
			->method('setSystemValues')
			->with([
				'enable_previews' => null,
				'preview_max_x' => null,
				'preview_max_y' => 1024,
				'preview_max_memory' => -1,
				'preview_max_filesize_image' => null,
				'preview_concurrency_new' => 2,
				'preview_concurrency_all' => null,
				'preview_expiration_days' => null,
			]);
		$this->appConfig->expects($this->once())->method('deleteKey')->with('preview', 'jpeg_quality');
		$this->appConfig->expects($this->once())->method('setValueInt')->with('preview', 'webp_quality', 60);

		$this->service->setSettings(true, 4096, 1024, -1, null, 80, 60, 2, null, 0);
	}

	public function testDisablingPreviewsIsStored(): void {
		$this->config->expects($this->once())
			->method('setSystemValues')
			->with($this->callback(fn (array $values) => $values['enable_previews'] === false));

		$this->service->setSettings(false, null, null, null, null, null, null, null, null, null);
	}

	public static function dataOutOfRange(): array {
		return [
			'width' => [[0, null, null, null, null, null, null, null, null]],
			'memory' => [[null, null, -2, null, null, null, null, null, null]],
			'quality too low' => [[null, null, null, null, 0, null, null, null, null]],
			'quality too high' => [[null, null, null, null, null, 101, null, null, null]],
			'concurrency' => [[null, null, null, null, null, null, 0, null, null]],
			'expiration' => [[null, null, null, null, null, null, null, null, -1]],
		];
	}

	#[DataProvider('dataOutOfRange')]
	public function testOutOfRangeValuesAreRejected(array $values): void {
		$this->config->expects($this->never())->method('setSystemValues');
		$this->expectException(\InvalidArgumentException::class);

		$this->service->setSettings(true, ...$values);
	}

	public function testProvidersAreStoredInOrder(): void {
		$this->systemConfig = ['enabledPreviewProviders' => ['OCA\\Foo\\Provider']];
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('enabledPreviewProviders', [HEIC::class, JPEG::class, 'OCA\\Foo\\Provider']);

		$this->service->setProviders(['\\' . HEIC::class, JPEG::class, 'OCA\\Foo\\Provider', JPEG::class]);
	}

	public function testUnknownProvidersAreRejected(): void {
		$this->config->expects($this->never())->method('setSystemValue');
		$this->expectException(\InvalidArgumentException::class);

		$this->service->setProviders([JPEG::class, 'OCA\\Evil\\Provider']);
	}

	public function testResetRemovesTheProviderList(): void {
		$this->config->expects($this->once())->method('deleteSystemValue')->with('enabledPreviewProviders');

		$this->service->resetProviders();
	}
}
