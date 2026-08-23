<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Preview\Failure\PreviewFailureService;
use OCP\IAppConfig;
use OCP\IConfig;

/**
 * Typed read/write helper for preview system and app config used by the admin UI
 * and by preview generation/HTTP layers.
 */
class PreviewAdminConfig {
	public const MIME_PRESETS = [
		'image/heic',
		'image/heif',
		'image/jpeg',
		'application/pdf',
	];

	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
	) {
	}

	/**
	 * Default enabledPreviewProviders list used when the key is unset.
	 *
	 * @return list<class-string>
	 */
	public static function getDefaultEnabledProviders(): array {
		return [
			MarkDown::class,
			TXT::class,
			OpenDocument::class,
			PNG::class,
			JPEG::class,
			GIF::class,
			BMP::class,
			XBitmap::class,
			Krita::class,
			WebP::class,
		];
	}

	/**
	 * Known core providers shown in the admin UI (enabled and disabled).
	 *
	 * @return list<array{class: string, name: string, mime: string}>
	 */
	public static function getProviderCatalog(): array {
		return [
			['class' => PNG::class, 'name' => 'PNG', 'mime' => 'image/png'],
			['class' => JPEG::class, 'name' => 'JPEG', 'mime' => 'image/jpeg'],
			['class' => GIF::class, 'name' => 'GIF', 'mime' => 'image/gif'],
			['class' => BMP::class, 'name' => 'BMP', 'mime' => 'image/bmp'],
			['class' => XBitmap::class, 'name' => 'XBitmap', 'mime' => 'image/x-xbitmap'],
			['class' => WebP::class, 'name' => 'WebP', 'mime' => 'image/webp'],
			['class' => Krita::class, 'name' => 'Krita', 'mime' => 'application/x-krita'],
			['class' => HEIC::class, 'name' => 'HEIC', 'mime' => 'image/heic, image/heif'],
			['class' => TIFF::class, 'name' => 'TIFF', 'mime' => 'image/tiff'],
			['class' => SVG::class, 'name' => 'SVG', 'mime' => 'image/svg+xml'],
			['class' => TGA::class, 'name' => 'TGA', 'mime' => 'image/tga'],
			['class' => SGI::class, 'name' => 'SGI', 'mime' => 'image/sgi'],
			['class' => Imaginary::class, 'name' => 'Imaginary', 'mime' => 'images (bmp, png, jpeg, gif, heic, heif, svg, tiff, webp), illustrator'],
			['class' => ImaginaryPDF::class, 'name' => 'Imaginary PDF', 'mime' => 'application/pdf'],
			['class' => PDF::class, 'name' => 'PDF', 'mime' => 'application/pdf'],
			['class' => Postscript::class, 'name' => 'Postscript', 'mime' => 'application/postscript'],
			['class' => Illustrator::class, 'name' => 'Illustrator', 'mime' => 'application/illustrator'],
			['class' => Photoshop::class, 'name' => 'Photoshop', 'mime' => 'application/x-photoshop'],
			['class' => Font::class, 'name' => 'Font', 'mime' => 'application/font-sfnt'],
			['class' => MarkDown::class, 'name' => 'Markdown', 'mime' => 'text/markdown'],
			['class' => TXT::class, 'name' => 'Plain text', 'mime' => 'text/plain'],
			['class' => OpenDocument::class, 'name' => 'OpenDocument', 'mime' => 'application/vnd.oasis.opendocument.*'],
			['class' => MSOfficeDoc::class, 'name' => 'MS Office Doc', 'mime' => 'application/msword'],
			['class' => MSOffice2003::class, 'name' => 'MS Office 2003', 'mime' => 'application/vnd.ms-*'],
			['class' => MSOffice2007::class, 'name' => 'MS Office 2007', 'mime' => 'application/vnd.openxmlformats-officedocument.*'],
			['class' => StarOffice::class, 'name' => 'StarOffice', 'mime' => 'application/vnd.sun.xml.*'],
			['class' => EMF::class, 'name' => 'EMF', 'mime' => 'image/emf'],
			['class' => MP3::class, 'name' => 'MP3', 'mime' => 'audio/mpeg'],
			['class' => Movie::class, 'name' => 'Movie', 'mime' => 'video/*'],
			['class' => Image::class, 'name' => 'Image (legacy)', 'mime' => 'enables PNG, JPEG, GIF, BMP, XBitmap, Krita, WebP'],
		];
	}

	/**
	 * @return list<string>
	 */
	public function getEnabledPreviewProviders(): array {
		$value = $this->config->getSystemValue('enabledPreviewProviders', self::getDefaultEnabledProviders());
		if (!is_array($value)) {
			return self::getDefaultEnabledProviders();
		}
		$providers = [];
		foreach ($value as $class) {
			if (!is_string($class) || $class === '') {
				continue;
			}
			$providers[] = self::normalizeClassName($class);
		}
		return array_values(array_unique($providers));
	}

	/**
	 * Snapshot of all preview settings for the admin UI.
	 *
	 * @return array<string, mixed>
	 */
	public function getSettings(): array {
		$maxX = $this->config->getSystemValue('preview_max_x', 4096);
		$maxY = $this->config->getSystemValue('preview_max_y', 4096);
		$enabled = $this->getEnabledPreviewProviders();
		$enabledSet = array_fill_keys($enabled, true);

		$providers = [];
		$seen = [];
		foreach ($enabled as $class) {
			$meta = $this->findCatalogEntry($class);
			$providers[] = [
				'class' => $class,
				'name' => $meta['name'] ?? $this->classBasename($class),
				'mime' => $meta['mime'] ?? '',
				'enabled' => true,
			];
			$seen[$class] = true;
		}
		foreach (self::getProviderCatalog() as $entry) {
			if (isset($seen[$entry['class']])) {
				continue;
			}
			$providers[] = [
				'class' => $entry['class'],
				'name' => $entry['name'],
				'mime' => $entry['mime'],
				'enabled' => isset($enabledSet[$entry['class']]),
			];
			$seen[$entry['class']] = true;
		}

		return [
			'enablePreviews' => $this->config->getSystemValueBool('enable_previews', true),
			'previewMaxX' => is_numeric($maxX) ? (int)$maxX : null,
			'previewMaxY' => is_numeric($maxY) ? (int)$maxY : null,
			'previewMaxMemory' => $this->config->getSystemValueInt('preview_max_memory', 256),
			'previewMaxFilesizeImage' => $this->config->getSystemValueInt('preview_max_filesize_image', 50),
			'jpegQuality' => $this->appConfig->getValueInt('preview', 'jpeg_quality', 80),
			'previewFormat' => $this->config->getSystemValueString('preview_format', 'jpeg'),
			'imaginaryUrl' => $this->config->getSystemValueString('preview_imaginary_url', ''),
			'imaginaryKey' => $this->config->getSystemValueString('preview_imaginary_key', ''),
			'providers' => $providers,
			'defaultEnabledProviders' => self::getDefaultEnabledProviders(),
			'mimePriority' => $this->getMimePriority(),
			'mimeDeny' => $this->getMimeDeny(),
			'mimePresets' => self::MIME_PRESETS,
			'cacheAuthenticated' => $this->getCachePolicyArray('preview_cache_authenticated', PreviewCachePolicy::defaultAuthenticated()),
			'cachePublic' => $this->getCachePolicyArray('preview_cache_public', PreviewCachePolicy::defaultPublic()),
			'failuresRetentionDays' => $this->config->getSystemValueInt('preview_failures_retention_days', PreviewFailureService::DEFAULT_RETENTION_DAYS),
			'failuresMaxRows' => $this->config->getSystemValueInt('preview_failures_max_rows', PreviewFailureService::DEFAULT_MAX_ROWS),
			'configIsReadonly' => $this->config->getSystemValueBool('config_is_read_only', false),
		];
	}

	/**
	 * Persist admin UI payload.
	 *
	 * @param array<string, mixed> $settings
	 * @throws \InvalidArgumentException
	 */
	public function setSettings(array $settings): void {
		if (array_key_exists('enablePreviews', $settings)) {
			$this->config->setSystemValue('enable_previews', (bool)$settings['enablePreviews']);
		}
		if (array_key_exists('previewMaxX', $settings)) {
			$this->setNullableInt('preview_max_x', $settings['previewMaxX'], 1);
		}
		if (array_key_exists('previewMaxY', $settings)) {
			$this->setNullableInt('preview_max_y', $settings['previewMaxY'], 1);
		}
		if (array_key_exists('previewMaxMemory', $settings)) {
			$this->setIntKey('preview_max_memory', $settings['previewMaxMemory'], -1);
		}
		if (array_key_exists('previewMaxFilesizeImage', $settings)) {
			$this->setIntKey('preview_max_filesize_image', $settings['previewMaxFilesizeImage'], -1);
		}
		if (array_key_exists('jpegQuality', $settings)) {
			$quality = $this->toInt($settings['jpegQuality']);
			if ($quality === null || $quality < 1 || $quality > 100) {
				throw new \InvalidArgumentException('JPEG quality must be between 1 and 100');
			}
			$this->appConfig->setValueInt('preview', 'jpeg_quality', $quality);
		}
		if (array_key_exists('previewFormat', $settings)) {
			$format = is_string($settings['previewFormat']) ? strtolower($settings['previewFormat']) : '';
			if (!in_array($format, ['jpeg', 'webp'], true)) {
				throw new \InvalidArgumentException('Preview format must be jpeg or webp');
			}
			$this->config->setSystemValue('preview_format', $format);
		}
		if (array_key_exists('imaginaryUrl', $settings)) {
			$this->config->setSystemValue('preview_imaginary_url', $this->validateImaginaryUrl((string)$settings['imaginaryUrl']));
		}
		if (array_key_exists('imaginaryKey', $settings)) {
			$key = is_string($settings['imaginaryKey']) ? $settings['imaginaryKey'] : '';
			$this->config->setSystemValue('preview_imaginary_key', $key);
		}
		if (array_key_exists('providers', $settings)) {
			$this->setEnabledProvidersFromRows($settings['providers'], (bool)($settings['confirmEmptyProviders'] ?? false));
		} elseif (array_key_exists('enabledPreviewProviders', $settings)) {
			$this->setEnabledProviders($settings['enabledPreviewProviders'], (bool)($settings['confirmEmptyProviders'] ?? false));
		}
		if (array_key_exists('mimePriority', $settings)) {
			$this->config->setSystemValue('preview_provider_mime_priority', $this->normalizeMimeMap($settings['mimePriority']));
		}
		if (array_key_exists('mimeDeny', $settings)) {
			$this->config->setSystemValue('preview_provider_mime_deny', $this->normalizeMimeMap($settings['mimeDeny']));
		}
		if (array_key_exists('cacheAuthenticated', $settings)) {
			$this->config->setSystemValue('preview_cache_authenticated', $this->normalizeCachePolicy($settings['cacheAuthenticated'], 'private'));
		}
		if (array_key_exists('cachePublic', $settings)) {
			$this->config->setSystemValue('preview_cache_public', $this->normalizeCachePolicy($settings['cachePublic'], 'private'));
		}
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function getMimePriority(): array {
		return $this->readMimeMap('preview_provider_mime_priority');
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function getMimeDeny(): array {
		return $this->readMimeMap('preview_provider_mime_deny');
	}

	public function validateImaginaryUrl(string $url): string {
		$url = trim($url);
		if ($url === '') {
			return '';
		}
		$parts = parse_url($url);
		if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
			throw new \InvalidArgumentException('Imaginary URL must be a valid http(s) URL');
		}
		if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
			throw new \InvalidArgumentException('Imaginary URL must use http or https');
		}
		return $url;
	}

	public static function normalizeClassName(string $class): string {
		return ltrim($class, '\\');
	}

	/**
	 * @param list<mixed> $providers
	 * @return list<string>
	 */
	public function setEnabledProviders(array $providers, bool $confirmEmpty = false): array {
		$normalized = [];
		foreach ($providers as $class) {
			if (!is_string($class) || $class === '') {
				continue;
			}
			$class = self::normalizeClassName($class);
			if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $class)) {
				throw new \InvalidArgumentException('Invalid preview provider class');
			}
			$normalized[] = $class;
		}
		$normalized = array_values(array_unique($normalized));
		if ($normalized === [] && !$confirmEmpty) {
			throw new \InvalidArgumentException('Refusing to write an empty preview provider list');
		}
		$this->config->setSystemValue('enabledPreviewProviders', $normalized);
		return $normalized;
	}

	/**
	 * @param list<mixed> $rows
	 */
	private function setEnabledProvidersFromRows(array $rows, bool $confirmEmpty): void {
		$enabled = [];
		foreach ($rows as $row) {
			if (!is_array($row) || !isset($row['class']) || !is_string($row['class'])) {
				continue;
			}
			if (!($row['enabled'] ?? false)) {
				continue;
			}
			$enabled[] = $row['class'];
		}
		$this->setEnabledProviders($enabled, $confirmEmpty);
	}

	private function setNullableInt(string $key, mixed $value, int $min): void {
		if ($value === null || $value === '') {
			$this->config->deleteSystemValue($key);
			return;
		}
		$this->setIntKey($key, $value, $min);
	}

	private function setIntKey(string $key, mixed $value, int $min): void {
		$int = $this->toInt($value);
		if ($int === null || $int < $min) {
			throw new \InvalidArgumentException('Invalid integer for ' . $key);
		}
		$this->config->setSystemValue($key, $int);
	}

	private function toInt(mixed $value): ?int {
		if (is_int($value)) {
			return $value;
		}
		if (is_string($value) && is_numeric($value)) {
			return (int)$value;
		}
		if (is_float($value)) {
			return (int)$value;
		}
		return null;
	}

	/**
	 * @return array<string, list<string>>
	 */
	private function readMimeMap(string $key): array {
		$value = $this->config->getSystemValue($key, []);
		if (!is_array($value)) {
			return [];
		}
		try {
			return $this->normalizeMimeMap($value);
		} catch (\InvalidArgumentException) {
			return [];
		}
	}

	/**
	 * @param mixed $value
	 * @return array<string, list<string>>
	 */
	private function normalizeMimeMap(mixed $value): array {
		if (!is_array($value)) {
			throw new \InvalidArgumentException('MIME map must be an object of mime => providers[]');
		}
		$out = [];
		foreach ($value as $mime => $classes) {
			if (!is_string($mime) || $mime === '' || !is_array($classes)) {
				continue;
			}
			$mime = strtolower(trim($mime));
			if (!preg_match('/^[a-z0-9._+-]+\/[a-z0-9._+-]+$/', $mime) && $mime !== '*/*') {
				throw new \InvalidArgumentException('Invalid MIME type: ' . $mime);
			}
			$normalized = [];
			foreach ($classes as $class) {
				if (!is_string($class) || $class === '') {
					continue;
				}
				$class = self::normalizeClassName($class);
				if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $class)) {
					throw new \InvalidArgumentException('Invalid preview provider class');
				}
				$normalized[] = $class;
			}
			$out[$mime] = array_values(array_unique($normalized));
		}
		return $out;
	}

	/**
	 * @param mixed $value
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}
	 */
	private function normalizeCachePolicy(mixed $value, string $defaultVisibility): array {
		if (!is_array($value)) {
			throw new \InvalidArgumentException('Cache policy must be an object');
		}
		$visibility = is_string($value['visibility'] ?? null) ? strtolower($value['visibility']) : $defaultVisibility;
		if (!in_array($visibility, ['private', 'public'], true)) {
			throw new \InvalidArgumentException('Cache visibility must be private or public');
		}
		$maxAge = $this->toInt($value['max_age'] ?? $value['maxAge'] ?? 86400);
		if ($maxAge === null || $maxAge < 0) {
			throw new \InvalidArgumentException('Cache max-age must be >= 0');
		}
		$sMaxAgeRaw = $value['s_maxage'] ?? $value['sMaxAge'] ?? null;
		$sMaxAge = ($sMaxAgeRaw === null || $sMaxAgeRaw === '') ? null : $this->toInt($sMaxAgeRaw);
		if ($sMaxAge !== null && $sMaxAge < 0) {
			throw new \InvalidArgumentException('Cache s-maxage must be >= 0');
		}
		$raw = $value['cache_control'] ?? $value['cacheControl'] ?? '';
		if (!is_string($raw)) {
			$raw = '';
		}
		return [
			'visibility' => $visibility,
			'max_age' => $maxAge,
			's_maxage' => $sMaxAge,
			'immutable' => (bool)($value['immutable'] ?? false),
			'cache_control' => trim($raw),
		];
	}

	/**
	 * @param array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control?: string} $default
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}
	 */
	private function getCachePolicyArray(string $key, array $default): array {
		$value = $this->config->getSystemValue($key, $default);
		if (!is_array($value)) {
			$value = $default;
		}
		try {
			return $this->normalizeCachePolicy($value, $default['visibility']);
		} catch (\InvalidArgumentException) {
			return $default + ['cache_control' => ''];
		}
	}

	/**
	 * @return array{class: string, name: string, mime: string}|null
	 */
	private function findCatalogEntry(string $class): ?array {
		foreach (self::getProviderCatalog() as $entry) {
			if ($entry['class'] === $class) {
				return $entry;
			}
		}
		return null;
	}

	private function classBasename(string $class): string {
		$parts = explode('\\', $class);
		return (string)end($parts);
	}
}
