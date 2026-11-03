<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Service;

use OC\Preview\AVIF;
use OC\Preview\AVIFImagick;
use OC\Preview\BMP;
use OC\Preview\CDR;
use OC\Preview\EMF;
use OC\Preview\Font;
use OC\Preview\GIF;
use OC\Preview\HEIC;
use OC\Preview\Illustrator;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\ImaginaryPDF;
use OC\Preview\JP2;
use OC\Preview\JPEG;
use OC\Preview\Krita;
use OC\Preview\MarkDown;
use OC\Preview\Movie;
use OC\Preview\MP3;
use OC\Preview\MSOffice2003;
use OC\Preview\MSOffice2007;
use OC\Preview\MSOfficeDoc;
use OC\Preview\OpenDocument;
use OC\Preview\PDF;
use OC\Preview\Photoshop;
use OC\Preview\PNG;
use OC\Preview\Postscript;
use OC\Preview\PreviewProviderDefaults;
use OC\Preview\SGI;
use OC\Preview\StarOffice;
use OC\Preview\SVG;
use OC\Preview\TGA;
use OC\Preview\TIFF;
use OC\Preview\TXT;
use OC\Preview\WebP;
use OC\Preview\XBitmap;
use OCA\Settings\ResponseDefinitions;
use OCP\IAppConfig;
use OCP\IBinaryFinder;
use OCP\IConfig;

/**
 * Reads and writes the preview settings shown on the Previews admin page.
 *
 * Binary paths and the Imaginary URL are reported but never written here:
 * they stay config.php only.
 *
 * @psalm-import-type SettingsPreviewSettings from ResponseDefinitions
 * @psalm-import-type SettingsPreviewProvider from ResponseDefinitions
 */
class PreviewSettingsService {
	/** System config keys and their defaults, as read by the preview code */
	private const SYSTEM_DEFAULTS = [
		'preview_max_x' => 4096,
		'preview_max_y' => 4096,
		'preview_max_memory' => 256,
		'preview_max_filesize_image' => 50,
		'preview_concurrency_new' => null,
		'preview_concurrency_all' => null,
		'preview_expiration_days' => 0,
	];

	/** App config keys of the `preview` app and their defaults */
	private const QUALITY_DEFAULTS = [
		'jpeg_quality' => 80,
		'webp_quality' => 80,
	];

	/**
	 * Core providers with their source mimetypes and what they need to run.
	 * Imagick providers carry the format queried from ImageMagick.
	 *
	 * @var array<class-string, array{mimetypes: string, requirement: 'none'|'imagick'|'office'|'ffmpeg'|'imaginary', format?: string}>
	 */
	private const CATALOG = [
		PNG::class => ['mimetypes' => 'image/png', 'requirement' => 'none'],
		JPEG::class => ['mimetypes' => 'image/jpeg', 'requirement' => 'none'],
		GIF::class => ['mimetypes' => 'image/gif', 'requirement' => 'none'],
		BMP::class => ['mimetypes' => 'image/bmp', 'requirement' => 'none'],
		XBitmap::class => ['mimetypes' => 'image/x-xbitmap', 'requirement' => 'none'],
		WebP::class => ['mimetypes' => 'image/webp', 'requirement' => 'none'],
		AVIF::class => ['mimetypes' => 'image/avif', 'requirement' => 'none'],
		CDR::class => ['mimetypes' => 'application/coreldraw', 'requirement' => 'none'],
		Krita::class => ['mimetypes' => 'application/x-krita', 'requirement' => 'none'],
		MarkDown::class => ['mimetypes' => 'text/markdown', 'requirement' => 'none'],
		TXT::class => ['mimetypes' => 'text/plain', 'requirement' => 'none'],
		OpenDocument::class => ['mimetypes' => 'application/vnd.oasis.opendocument.*', 'requirement' => 'none'],
		MP3::class => ['mimetypes' => 'audio/mpeg', 'requirement' => 'none'],
		Imaginary::class => ['mimetypes' => 'image/*, application/illustrator', 'requirement' => 'imaginary'],
		ImaginaryPDF::class => ['mimetypes' => 'application/pdf', 'requirement' => 'imaginary'],
		HEIC::class => ['mimetypes' => 'image/heic, image/heif', 'requirement' => 'imagick', 'format' => 'HEIC'],
		AVIFImagick::class => ['mimetypes' => 'image/avif', 'requirement' => 'imagick', 'format' => 'AVIF'],
		JP2::class => ['mimetypes' => 'image/jp2', 'requirement' => 'imagick', 'format' => 'JP2'],
		SVG::class => ['mimetypes' => 'image/svg+xml', 'requirement' => 'imagick', 'format' => 'SVG'],
		TIFF::class => ['mimetypes' => 'image/tiff', 'requirement' => 'imagick', 'format' => 'TIFF'],
		TGA::class => ['mimetypes' => 'image/tga', 'requirement' => 'imagick', 'format' => 'TGA'],
		SGI::class => ['mimetypes' => 'image/sgi', 'requirement' => 'imagick', 'format' => 'SGI'],
		PDF::class => ['mimetypes' => 'application/pdf', 'requirement' => 'imagick', 'format' => 'PDF'],
		Illustrator::class => ['mimetypes' => 'application/illustrator', 'requirement' => 'imagick', 'format' => 'AI'],
		Photoshop::class => ['mimetypes' => 'application/x-photoshop', 'requirement' => 'imagick', 'format' => 'PSD'],
		Postscript::class => ['mimetypes' => 'application/postscript', 'requirement' => 'imagick', 'format' => 'EPS'],
		Font::class => ['mimetypes' => 'application/font-sfnt', 'requirement' => 'imagick', 'format' => 'TTF'],
		MSOfficeDoc::class => ['mimetypes' => 'application/msword', 'requirement' => 'office'],
		MSOffice2003::class => ['mimetypes' => 'application/vnd.ms-*', 'requirement' => 'office'],
		MSOffice2007::class => ['mimetypes' => 'application/vnd.openxmlformats-officedocument.*', 'requirement' => 'office'],
		StarOffice::class => ['mimetypes' => 'application/vnd.sun.xml.*', 'requirement' => 'office'],
		EMF::class => ['mimetypes' => 'image/emf', 'requirement' => 'office'],
		Movie::class => ['mimetypes' => 'video/*', 'requirement' => 'ffmpeg'],
	];

	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly IMagickSupport $imagickSupport,
		private readonly IBinaryFinder $binaryFinder,
	) {
	}

	public function isConfigReadOnly(): bool {
		return $this->config->getSystemValueBool('config_is_read_only', false);
	}

	/**
	 * @return SettingsPreviewSettings
	 */
	public function getSettings(): array {
		$ffmpeg = $this->findBinary('preview_ffmpeg_path', ['ffmpeg']);
		$office = $this->findBinary('preview_libreoffice_path', ['libreoffice', 'openoffice']);
		$imaginary = $this->config->getSystemValueString('preview_imaginary_url', '') !== '';

		return [
			'configIsReadOnly' => $this->isConfigReadOnly(),
			'enabled' => $this->config->getSystemValueBool('enable_previews', true),
			'maxX' => $this->getStoredInt('preview_max_x'),
			'maxY' => $this->getStoredInt('preview_max_y'),
			'maxMemory' => $this->getStoredInt('preview_max_memory'),
			'maxFilesizeImage' => $this->getStoredInt('preview_max_filesize_image'),
			'jpegQuality' => $this->getStoredQuality('jpeg_quality'),
			'webpQuality' => $this->getStoredQuality('webp_quality'),
			'concurrencyNew' => $this->getStoredInt('preview_concurrency_new'),
			'concurrencyAll' => $this->getStoredInt('preview_concurrency_all'),
			'expirationDays' => $this->getStoredInt('preview_expiration_days'),
			'providersConfigured' => is_array($this->config->getSystemValue('enabledPreviewProviders', null)),
			'providers' => $this->getProviders($ffmpeg !== null, $office !== null, $imaginary),
			'dependencies' => [
				'imagick' => $this->imagickSupport->hasExtension(),
				'ffmpeg' => $ffmpeg,
				'office' => $office,
				'imaginary' => $imaginary,
			],
		];
	}

	/**
	 * Store the limits. `null`, or a value equal to the default, removes the
	 * key so the built-in default applies.
	 *
	 * @throws \InvalidArgumentException when a value is out of range
	 */
	public function setSettings(
		bool $enabled,
		?int $maxX,
		?int $maxY,
		?int $maxMemory,
		?int $maxFilesizeImage,
		?int $jpegQuality,
		?int $webpQuality,
		?int $concurrencyNew,
		?int $concurrencyAll,
		?int $expirationDays,
	): void {
		$this->assertInRange('maxX', $maxX, 1);
		$this->assertInRange('maxY', $maxY, 1);
		$this->assertInRange('maxMemory', $maxMemory, -1);
		$this->assertInRange('maxFilesizeImage', $maxFilesizeImage, -1);
		$this->assertInRange('jpegQuality', $jpegQuality, 1, 100);
		$this->assertInRange('webpQuality', $webpQuality, 1, 100);
		$this->assertInRange('concurrencyNew', $concurrencyNew, 1);
		$this->assertInRange('concurrencyAll', $concurrencyAll, 1);
		$this->assertInRange('expirationDays', $expirationDays, 0);

		$values = [
			'preview_max_x' => $maxX,
			'preview_max_y' => $maxY,
			'preview_max_memory' => $maxMemory,
			'preview_max_filesize_image' => $maxFilesizeImage,
			'preview_concurrency_new' => $concurrencyNew,
			'preview_concurrency_all' => $concurrencyAll,
			'preview_expiration_days' => $expirationDays,
		];
		$system = ['enable_previews' => $enabled ? null : false];
		foreach ($values as $key => $value) {
			$system[$key] = $value === self::SYSTEM_DEFAULTS[$key] ? null : $value;
		}
		$this->config->setSystemValues($system);

		foreach (['jpeg_quality' => $jpegQuality, 'webp_quality' => $webpQuality] as $key => $value) {
			if ($value === null || $value === self::QUALITY_DEFAULTS[$key]) {
				$this->appConfig->deleteKey('preview', $key);
			} else {
				$this->appConfig->setValueInt('preview', $key, $value);
			}
		}
	}

	/**
	 * Store the enabled providers, in try-order.
	 *
	 * @param list<string> $providers
	 * @throws \InvalidArgumentException when a class is not a known provider
	 */
	public function setProviders(array $providers): void {
		$known = array_merge(array_keys(self::CATALOG), $this->getEnabledProviders());
		$enabled = [];
		foreach ($providers as $class) {
			$class = ltrim($class, '\\');
			if (!in_array($class, $known, true)) {
				throw new \InvalidArgumentException('Unknown preview provider: ' . $class);
			}
			$enabled[] = $class;
		}
		$this->config->setSystemValue('enabledPreviewProviders', array_values(array_unique($enabled)));
	}

	/**
	 * Remove the provider list so the default list applies again.
	 */
	public function resetProviders(): void {
		$this->config->deleteSystemValue('enabledPreviewProviders');
	}

	/**
	 * @return list<string>
	 */
	private function getEnabledProviders(): array {
		$configured = $this->config->getSystemValue('enabledPreviewProviders', null);
		if (!is_array($configured)) {
			return PreviewProviderDefaults::getRecommendedEnabledProviders(
				$this->config->getSystemValueString('preview_imaginary_url', '') !== '',
				$this->imagickSupport->hasExtension() && $this->imagickSupport->supportsFormat('HEIC'),
			);
		}
		return array_values(array_unique(array_map(
			static fn (string $class): string => ltrim($class, '\\'),
			array_filter($configured, static fn (mixed $class): bool => is_string($class) && $class !== ''),
		)));
	}

	/**
	 * Enabled providers first in try-order, then the other core providers.
	 *
	 * @return list<SettingsPreviewProvider>
	 */
	private function getProviders(bool $ffmpeg, bool $office, bool $imaginary): array {
		$enabled = $this->getEnabledProviders();
		$classes = array_values(array_unique(array_merge($enabled, array_keys(self::CATALOG))));

		return array_map(function (string $class) use ($enabled, $ffmpeg, $office, $imaginary): array {
			$entry = self::CATALOG[$class] ?? ['mimetypes' => '', 'requirement' => 'none'];
			$available = match ($entry['requirement']) {
				'imagick' => $this->imagickSupport->hasExtension() && $this->imagickSupport->supportsFormat($entry['format'] ?? ''),
				'office' => $office,
				'ffmpeg' => $ffmpeg,
				'imaginary' => $imaginary,
				'none' => true,
			};
			return [
				'class' => $class,
				'name' => substr(strrchr('\\' . $class, '\\'), 1),
				'mimetypes' => $entry['mimetypes'],
				'requirement' => $entry['requirement'],
				'available' => $available,
				'enabled' => in_array($class, $enabled, true),
			];
		}, $classes);
	}

	/**
	 * Same lookup as {@see \OC\PreviewManager}: the configured path, else PATH.
	 *
	 * @param list<string> $names
	 */
	private function findBinary(string $key, array $names): ?string {
		$configured = $this->config->getSystemValue($key, null);
		if (is_string($configured) && $configured !== '') {
			return $configured;
		}
		foreach ($names as $name) {
			$path = $this->binaryFinder->findBinaryPath($name);
			if ($path !== false) {
				return $path;
			}
		}
		return null;
	}

	private function getStoredInt(string $key): ?int {
		$value = $this->config->getSystemValue($key, null);
		return is_numeric($value) ? (int)$value : null;
	}

	private function getStoredQuality(string $key): ?int {
		return $this->appConfig->hasKey('preview', $key, null) ? $this->appConfig->getValueInt('preview', $key) : null;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function assertInRange(string $name, ?int $value, int $min, ?int $max = null): void {
		if ($value !== null && ($value < $min || ($max !== null && $value > $max))) {
			throw new \InvalidArgumentException(sprintf('%s is out of range', $name));
		}
	}
}
