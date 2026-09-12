<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\HEIC;
use OC\Preview\Image;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\ImaginaryPDF;
use OC\Preview\JPEG;
use OC\Preview\MarkDown;
use OC\Preview\Movie;
use OC\Preview\MSOfficeDoc;
use OC\Preview\OpenDocument;
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
		$this->assertSame(
			$settings['defaultEnabledProviders'],
			array_slice($settings['defaultProviderOrder'], 0, count($settings['defaultEnabledProviders'])),
		);
		$this->assertContains(Movie::class, $settings['defaultProviderOrder']);
		$this->assertContains(PDF::class, $settings['defaultProviderOrder']);
		$this->assertNotEmpty($settings['providers']);
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
			'previewConcurrencyNew' => 2,
			'previewConcurrencyAll' => 6,
		]);
		$this->assertSame(90, $calls['preview_expiration_days']);
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
		$this->assertSame('/usr/bin/ffmpeg', $settings['detection']['ffmpegDetectedPath']);
		$this->assertFalse($settings['detection']['officeFound']);
		$this->assertTrue($byClass[HEIC::class]['available']);
		$this->assertTrue($byClass[HEIC::class]['unsupported']);
		$this->assertFalse($byClass[PDF::class]['available']);
		$this->assertTrue($byClass[PDF::class]['unsupported']);
		$this->assertTrue($byClass[Movie::class]['available']);
		$this->assertTrue($byClass[Movie::class]['unsupported']);
		$this->assertFalse($byClass[Movie::class]['enabled']);
		$this->assertFalse($byClass[MSOfficeDoc::class]['available']);
		$this->assertTrue($byClass[MSOfficeDoc::class]['unsupported']);
		$this->assertFalse($byClass[Imaginary::class]['available']);
		$this->assertFalse($byClass[Imaginary::class]['unsupported']);
		$this->assertTrue($byClass[JPEG::class]['available']);
		$this->assertFalse($byClass[JPEG::class]['unsupported']);
		$this->assertContains('image/heic', $byClass[HEIC::class]['sourceMimes']);
		$this->assertContains('image/heic', $byClass[Imaginary::class]['sourceMimes']);
	}

	public function testMovieStaysDisabledWhenProviderListIsExplicit(): void {
		$finder = $this->createMock(IBinaryFinder::class);
		$finder->method('findBinaryPath')->willReturnCallback(fn (string $name) => $name === 'ffmpeg' ? '/usr/bin/ffmpeg' : false);

		$this->config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) {
			if ($key === 'enabledPreviewProviders') {
				return [JPEG::class, PNG::class];
			}
			return $default;
		});
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(fn (string $key, string $default) => $default);
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

		$config = new PreviewAdminConfig($this->config, $this->appConfig, null, $finder);
		$settings = $config->getSettings();
		$byClass = [];
		foreach ($settings['providers'] as $row) {
			$byClass[$row['class']] = $row;
		}

		$this->assertTrue($byClass[Movie::class]['available']);
		$this->assertFalse($byClass[Movie::class]['enabled']);
	}

	public function testProviderTableOrderDoesNotChangeWhenDisabled(): void {
		$enabled = [JPEG::class, PNG::class, MarkDown::class];
		$this->config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) use (&$enabled) {
			if ($key === 'enabledPreviewProviders') {
				return $enabled;
			}
			return $default;
		});
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(fn (string $key, string $default) => $default);
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

		$withMarkdown = $this->adminConfig->getSettings();
		$enabledIndex = array_search(MarkDown::class, array_column($withMarkdown['providers'], 'class'), true);

		$enabled = [JPEG::class, PNG::class];
		$withoutMarkdown = $this->adminConfig->getSettings();
		$disabledIndex = array_search(MarkDown::class, array_column($withoutMarkdown['providers'], 'class'), true);

		$this->assertNotFalse($enabledIndex);
		$this->assertSame($enabledIndex, $disabledIndex);
		$this->assertTrue($withMarkdown['providers'][$enabledIndex]['enabled']);
		$this->assertFalse($withoutMarkdown['providers'][$disabledIndex]['enabled']);
	}

	public function testSavedProviderTableOrderKeepsDisabledRowsInPlace(): void {
		$stored = [];
		$this->config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) use (&$stored) {
			return $stored[$key] ?? $default;
		});
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(function (string $key, string $default) use (&$stored) {
			$value = $stored[$key] ?? $default;
			return is_string($value) ? $value : $default;
		});
		$this->config->method('setSystemValue')->willReturnCallback(function (string $key, mixed $value) use (&$stored): void {
			$stored[$key] = $value;
		});

		$this->adminConfig->setSettings([
			'providers' => [
				['class' => JPEG::class, 'enabled' => true],
				['class' => MarkDown::class, 'enabled' => false],
				['class' => PNG::class, 'enabled' => true],
			],
		]);

		$classes = array_column($this->adminConfig->getSettings()['providers'], 'class');
		$this->assertSame([JPEG::class, MarkDown::class, PNG::class], array_slice($classes, 0, 3));
		$this->assertSame([JPEG::class, PNG::class], $stored['enabledPreviewProviders']);
	}

	public function testRecommendedDefaultsPutImaginaryFirstWhenUrlIsSet(): void {
		$this->config->method('getSystemValue')->willReturnCallback(fn (string $key, mixed $default) => $default);
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(function (string $key, string $default) {
			return $key === 'preview_imaginary_url' ? 'http://imaginary:9000' : $default;
		});
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

		$config = new PreviewAdminConfig($this->config, $this->appConfig);
		$settings = $config->getSettings();
		$this->assertSame(Imaginary::class, $settings['providers'][0]['class']);
		$this->assertTrue($settings['providers'][0]['enabled']);
		$this->assertSame(Imaginary::class, $settings['defaultEnabledProviders'][0]);
		$this->assertNotContains(HEIC::class, $settings['defaultEnabledProviders']);
	}

	public function testRecommendedDefaultsAddHeicFallbackWhenImagickSupportsIt(): void {
		$imagick = $this->createMock(IMagickSupport::class);
		$imagick->method('hasExtension')->willReturn(true);
		$imagick->method('supportsFormat')->willReturnCallback(fn (string $format) => $format === 'HEIC');

		$this->config->method('getSystemValue')->willReturnCallback(fn (string $key, mixed $default) => $default);
		$this->config->method('getSystemValueBool')->willReturnCallback(fn (string $key, bool $default) => $default);
		$this->config->method('getSystemValueInt')->willReturnCallback(fn (string $key, int $default) => $default);
		$this->config->method('getSystemValueString')->willReturnCallback(function (string $key, string $default) {
			return $key === 'preview_imaginary_url' ? 'http://imaginary:9000' : $default;
		});
		$this->appConfig->method('getValueInt')->willReturnCallback(fn (string $app, string $key, int $default) => $default);

		$config = new PreviewAdminConfig($this->config, $this->appConfig, $imagick);
		$settings = $config->getSettings();
		$this->assertSame(Imaginary::class, $settings['defaultEnabledProviders'][0]);
		$this->assertContains(HEIC::class, $settings['defaultEnabledProviders']);
		$byClass = [];
		foreach ($settings['providers'] as $row) {
			$byClass[$row['class']] = $row;
		}
		$this->assertTrue($byClass[HEIC::class]['enabled']);
		$this->assertTrue($byClass[HEIC::class]['available']);
		$this->assertTrue($byClass[HEIC::class]['unsupported']);
	}

	public function testSetSettingsDropsProvidersThatDoNotMeetRequirements(): void {
		$stored = [];
		$this->config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) use (&$stored) {
			return $stored[$key] ?? $default;
		});
		$this->config->method('getSystemValueString')->willReturnCallback(function (string $key, string $default) use (&$stored) {
			$value = $stored[$key] ?? $default;
			return is_string($value) ? $value : $default;
		});
		$this->config->method('setSystemValue')->willReturnCallback(function (string $key, mixed $value) use (&$stored): void {
			$stored[$key] = $value;
		});
		$this->config->method('deleteSystemValue')->willReturnCallback(function (string $key) use (&$stored): void {
			unset($stored[$key]);
		});

		$this->adminConfig->setSettings([
			'ffmpegPath' => '',
			'imaginaryUrl' => '',
			'providers' => [
				['class' => JPEG::class, 'enabled' => true],
				['class' => Movie::class, 'enabled' => true],
				['class' => Imaginary::class, 'enabled' => true],
				['class' => MSOfficeDoc::class, 'enabled' => true],
			],
		]);

		$this->assertSame([JPEG::class], $stored['enabledPreviewProviders']);
	}

	public function testSetSettingsKeepsMovieWhenFfmpegPathIsSet(): void {
		$stored = [];
		$this->config->method('getSystemValue')->willReturnCallback(function (string $key, mixed $default) use (&$stored) {
			return $stored[$key] ?? $default;
		});
		$this->config->method('getSystemValueString')->willReturnCallback(function (string $key, string $default) use (&$stored) {
			$value = $stored[$key] ?? $default;
			return is_string($value) ? $value : $default;
		});
		$this->config->method('setSystemValue')->willReturnCallback(function (string $key, mixed $value) use (&$stored): void {
			$stored[$key] = $value;
		});

		$this->adminConfig->setSettings([
			'ffmpegPath' => '/usr/bin/ffmpeg',
			'providers' => [
				['class' => JPEG::class, 'enabled' => true],
				['class' => Movie::class, 'enabled' => true],
			],
		]);

		$this->assertSame([JPEG::class, Movie::class], $stored['enabledPreviewProviders']);
		$this->assertSame('/usr/bin/ffmpeg', $stored['preview_ffmpeg_path']);
	}

	public function testUnsupportedProviderListMatchesDisabledByDefaultCatalog(): void {
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(JPEG::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(PNG::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(OpenDocument::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(Imaginary::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(ImaginaryPDF::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider(Image::class));
		$this->assertTrue(PreviewAdminConfig::isUnsupportedProvider(HEIC::class));
		$this->assertTrue(PreviewAdminConfig::isUnsupportedProvider(Movie::class));
		$this->assertTrue(PreviewAdminConfig::isUnsupportedProvider(PDF::class));
		$this->assertTrue(PreviewAdminConfig::isUnsupportedProvider(MSOfficeDoc::class));
		$this->assertFalse(PreviewAdminConfig::isUnsupportedProvider('OCA\\SomeApp\\Preview\\Custom'));
	}
}
