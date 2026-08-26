<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\Preview\Failure\PreviewFailureService;
use OCP\IAppConfig;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\Server;

/**
 * Typed read/write helper for preview system and app config used by the admin UI
 * and by preview generation/HTTP layers.
 */
class PreviewAdminConfig {
	/** @var list<string> */
	private const IMAGICK_FORMATS = ['SVG', 'TIFF', 'PDF', 'AI', 'PSD', 'EPS', 'TTF', 'HEIC', 'TGA', 'SGI'];

	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly ?IMagickSupport $imagickSupport = null,
		private readonly ?IBinaryFinder $binaryFinder = null,
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
	 * @return list<array{class: string, name: string, mime: string, requirement: string, imagickFormat: ?string}>
	 */
	public static function getProviderCatalog(): array {
		$imagick = static fn (string $class, string $name, string $mime, string $format): array => [
			'class' => $class,
			'name' => $name,
			'mime' => $mime,
			'requirement' => 'imagick',
			'imagickFormat' => $format,
		];
		$none = static fn (string $class, string $name, string $mime): array => [
			'class' => $class,
			'name' => $name,
			'mime' => $mime,
			'requirement' => 'none',
			'imagickFormat' => null,
		];
		return [
			$none(PNG::class, 'PNG', 'image/png'),
			$none(JPEG::class, 'JPEG', 'image/jpeg'),
			$none(GIF::class, 'GIF', 'image/gif'),
			$none(BMP::class, 'BMP', 'image/bmp'),
			$none(XBitmap::class, 'XBitmap', 'image/x-xbitmap'),
			$none(WebP::class, 'WebP', 'image/webp'),
			$none(Krita::class, 'Krita', 'application/x-krita'),
			$imagick(HEIC::class, 'HEIC', 'image/heic, image/heif', 'HEIC'),
			$imagick(TIFF::class, 'TIFF', 'image/tiff', 'TIFF'),
			$imagick(SVG::class, 'SVG', 'image/svg+xml', 'SVG'),
			$imagick(TGA::class, 'TGA', 'image/tga', 'TGA'),
			$imagick(SGI::class, 'SGI', 'image/sgi', 'SGI'),
			['class' => Imaginary::class, 'name' => 'Imaginary', 'mime' => 'images (bmp, png, jpeg, gif, heic, heif, svg, tiff, webp), illustrator', 'requirement' => 'imaginary', 'imagickFormat' => null],
			['class' => ImaginaryPDF::class, 'name' => 'Imaginary PDF', 'mime' => 'application/pdf', 'requirement' => 'imaginary', 'imagickFormat' => null],
			$imagick(PDF::class, 'PDF', 'application/pdf', 'PDF'),
			$imagick(Postscript::class, 'Postscript', 'application/postscript', 'EPS'),
			$imagick(Illustrator::class, 'Illustrator', 'application/illustrator', 'AI'),
			$imagick(Photoshop::class, 'Photoshop', 'application/x-photoshop', 'PSD'),
			$imagick(Font::class, 'Font', 'application/font-sfnt', 'TTF'),
			$none(MarkDown::class, 'Markdown', 'text/markdown'),
			$none(TXT::class, 'Plain text', 'text/plain'),
			$none(OpenDocument::class, 'OpenDocument', 'application/vnd.oasis.opendocument.*'),
			['class' => MSOfficeDoc::class, 'name' => 'MS Office Doc', 'mime' => 'application/msword', 'requirement' => 'office', 'imagickFormat' => null],
			['class' => MSOffice2003::class, 'name' => 'MS Office 2003', 'mime' => 'application/vnd.ms-*', 'requirement' => 'office', 'imagickFormat' => null],
			['class' => MSOffice2007::class, 'name' => 'MS Office 2007', 'mime' => 'application/vnd.openxmlformats-officedocument.*', 'requirement' => 'office', 'imagickFormat' => null],
			['class' => StarOffice::class, 'name' => 'StarOffice', 'mime' => 'application/vnd.sun.xml.*', 'requirement' => 'office', 'imagickFormat' => null],
			['class' => EMF::class, 'name' => 'EMF', 'mime' => 'image/emf', 'requirement' => 'office', 'imagickFormat' => null],
			$none(MP3::class, 'MP3', 'audio/mpeg'),
			['class' => Movie::class, 'name' => 'Movie', 'mime' => 'video/*', 'requirement' => 'ffmpeg', 'imagickFormat' => null],
			$none(Image::class, 'Image (legacy)', 'enables PNG, JPEG, GIF, BMP, XBitmap, Krita, WebP'),
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
		$detection = $this->getDetection();

		$providers = [];
		$seen = [];
		foreach ($enabled as $class) {
			$meta = $this->findCatalogEntry($class) ?? [
				'class' => $class,
				'name' => $this->classBasename($class),
				'mime' => '',
				'requirement' => 'none',
				'imagickFormat' => null,
			];
			$providers[] = $this->providerRow($meta, true, $detection);
			$seen[$class] = true;
		}
		foreach (self::getProviderCatalog() as $entry) {
			if (isset($seen[$entry['class']])) {
				continue;
			}
			$providers[] = $this->providerRow(
				$entry,
				$this->isProviderEnabledInUi($entry['class'], $enabledSet, $detection),
				$detection,
			);
			$seen[$entry['class']] = true;
		}

		$ffmpegConfigured = $this->configuredBinary('preview_ffmpeg_path');
		$ffprobeConfigured = $this->configuredBinary('preview_ffprobe_path');
		$officeConfigured = $this->configuredBinary('preview_libreoffice_path');

		return [
			'enablePreviews' => $this->config->getSystemValueBool('enable_previews', true),
			'previewMaxX' => is_numeric($maxX) ? (int)$maxX : null,
			'previewMaxY' => is_numeric($maxY) ? (int)$maxY : null,
			'previewMaxMemory' => $this->config->getSystemValueInt('preview_max_memory', 256),
			'previewMaxFilesizeImage' => $this->config->getSystemValueInt('preview_max_filesize_image', 50),
			'jpegQuality' => $this->appConfig->getValueInt('preview', 'jpeg_quality', 80),
			'webpQuality' => $this->appConfig->getValueInt('preview', 'webp_quality', 80),
			'previewFormat' => $this->config->getSystemValueString('preview_format', 'jpeg'),
			'previewConcurrencyNew' => $this->getOptionalInt('preview_concurrency_new'),
			'previewConcurrencyAll' => $this->getOptionalInt('preview_concurrency_all'),
			'previewExpirationDays' => $this->config->getSystemValueInt('preview_expiration_days', 0),
			'imaginaryUrl' => $this->config->getSystemValueString('preview_imaginary_url', ''),
			'imaginaryKey' => $this->config->getSystemValueString('preview_imaginary_key', ''),
			'ffmpegPath' => $ffmpegConfigured,
			'ffprobePath' => $ffprobeConfigured,
			'libreofficePath' => $officeConfigured,
			'providers' => $providers,
			'defaultEnabledProviders' => self::getDefaultEnabledProviders(),
			'cacheAuthenticated' => $this->getCachePolicyArray('preview_cache_authenticated', PreviewCachePolicy::defaultAuthenticated()),
			'cachePublic' => $this->getCachePolicyArray('preview_cache_public', PreviewCachePolicy::defaultPublic()),
			'failuresRetentionDays' => $this->config->getSystemValueInt('preview_failures_retention_days', PreviewFailureService::DEFAULT_RETENTION_DAYS),
			'failuresMaxRows' => $this->config->getSystemValueInt('preview_failures_max_rows', PreviewFailureService::DEFAULT_MAX_ROWS),
			'detection' => $detection,
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
			$this->setQuality('jpeg_quality', $settings['jpegQuality'], 'JPEG');
		}
		if (array_key_exists('webpQuality', $settings)) {
			$this->setQuality('webp_quality', $settings['webpQuality'], 'WebP');
		}
		if (array_key_exists('previewConcurrencyNew', $settings)) {
			$this->setOptionalPositiveInt('preview_concurrency_new', $settings['previewConcurrencyNew']);
		}
		if (array_key_exists('previewConcurrencyAll', $settings)) {
			$this->setOptionalPositiveInt('preview_concurrency_all', $settings['previewConcurrencyAll']);
		}
		if (array_key_exists('previewExpirationDays', $settings)) {
			$this->setIntKey('preview_expiration_days', $settings['previewExpirationDays'], 0);
		}
		if (array_key_exists('failuresRetentionDays', $settings)) {
			$this->setIntKey('preview_failures_retention_days', $settings['failuresRetentionDays'], 0);
		}
		if (array_key_exists('failuresMaxRows', $settings)) {
			$this->setIntKey('preview_failures_max_rows', $settings['failuresMaxRows'], 0);
		}
		if (array_key_exists('ffmpegPath', $settings)) {
			$this->setBinaryPath('preview_ffmpeg_path', $settings['ffmpegPath']);
		}
		if (array_key_exists('ffprobePath', $settings)) {
			$this->setBinaryPath('preview_ffprobe_path', $settings['ffprobePath']);
		}
		if (array_key_exists('libreofficePath', $settings)) {
			$this->setBinaryPath('preview_libreoffice_path', $settings['libreofficePath']);
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
		if (array_key_exists('cacheAuthenticated', $settings)) {
			$this->config->setSystemValue('preview_cache_authenticated', $this->normalizeCachePolicy($settings['cacheAuthenticated'], 'private'));
		}
		if (array_key_exists('cachePublic', $settings)) {
			$this->config->setSystemValue('preview_cache_public', $this->normalizeCachePolicy($settings['cachePublic'], 'private'));
		}
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
	 * @param mixed $value
	 * @return array{visibility: string, max_age: int, s_maxage: ?int, immutable: bool, cache_control: string}
	 */
	private function normalizeCachePolicy(mixed $value, string $defaultVisibility, bool $requirePublicSMaxAge = true): array {
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
		if ($visibility === 'private') {
			$sMaxAge = null;
		} elseif ($sMaxAge === null && $requirePublicSMaxAge) {
			throw new \InvalidArgumentException('Cache s-maxage is required when visibility is public');
		} elseif ($sMaxAge !== null && $sMaxAge < 0) {
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
			return $this->normalizeCachePolicy($value, $default['visibility'], false);
		} catch (\InvalidArgumentException) {
			return $default + ['cache_control' => ''];
		}
	}

	/**
	 * @return array{class: string, name: string, mime: string, requirement: string, imagickFormat: ?string}|null
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

	/**
	 * @param array{class: string, name: string, mime: string, requirement?: string, imagickFormat?: ?string} $entry
	 * @param array<string, mixed> $detection
	 * @return array<string, mixed>
	 */
	private function providerRow(array $entry, bool $enabled, array $detection): array {
		$requirement = $entry['requirement'] ?? 'none';
		$format = $entry['imagickFormat'] ?? null;
		return [
			'class' => $entry['class'],
			'name' => $entry['name'],
			'mime' => $entry['mime'],
			'enabled' => $enabled,
			'requirement' => $requirement,
			'imagickFormat' => $format,
			'available' => $this->isProviderAvailable($requirement, is_string($format) ? $format : null, $detection),
		];
	}

	/**
	 * @param array<string, mixed> $detection
	 */
	private function isProviderAvailable(string $requirement, ?string $imagickFormat, array $detection): bool {
		return match ($requirement) {
			'imagick' => (bool)$detection['imagick']
				&& ($imagickFormat === null || (bool)($detection['imagickFormats'][$imagickFormat] ?? false)),
			'ffmpeg' => (bool)$detection['ffmpegFound'],
			'office' => (bool)$detection['officeFound'],
			'imaginary' => (bool)$detection['imaginaryConfigured'],
			default => true,
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getDetection(): array {
		$imagick = $this->imagickSupport !== null && $this->imagickSupport->hasExtension();
		$formats = [];
		foreach (self::IMAGICK_FORMATS as $format) {
			$formats[$format] = $imagick && $this->imagickSupport !== null && $this->imagickSupport->supportsFormat($format);
		}

		$ffmpegDetected = $this->resolveBinary('preview_ffmpeg_path', ['ffmpeg']);
		$ffprobeDetected = $this->resolveBinary('preview_ffprobe_path', ['ffprobe']) ?? $ffmpegDetected;
		$officeDetected = $this->resolveBinary('preview_libreoffice_path', ['libreoffice', 'openoffice']);

		return [
			'imagick' => $imagick,
			'imagickFormats' => $formats,
			'ffmpegFound' => $ffmpegDetected !== null,
			'ffmpegDetectedPath' => $ffmpegDetected,
			'ffprobeFound' => $ffprobeDetected !== null,
			'ffprobeDetectedPath' => $ffprobeDetected,
			'officeFound' => $officeDetected !== null,
			'officeDetectedPath' => $officeDetected,
			'imaginaryConfigured' => $this->config->getSystemValueString('preview_imaginary_url', '') !== '',
			'cpuCount' => $this->detectCpuCount(),
		];
	}

	/**
	 * Movie is off by default. When ffmpeg is on PATH and the admin has never
	 * saved a custom provider list, show it enabled so detection matches the table.
	 *
	 * @param array<string, true> $enabledSet
	 * @param array<string, mixed> $detection
	 */
	private function isProviderEnabledInUi(string $class, array $enabledSet, array $detection): bool {
		if (isset($enabledSet[$class])) {
			return true;
		}
		if ($class !== Movie::class) {
			return false;
		}
		if (!($detection['ffmpegFound'] ?? false)) {
			return false;
		}
		return $this->config->getSystemValue('enabledPreviewProviders', null) === null;
	}

	/**
	 * @param list<string> $searchNames
	 */
	private function resolveBinary(string $configKey, array $searchNames): ?string {
		$configured = $this->configuredBinary($configKey);
		if ($configured !== '') {
			return $configured;
		}
		$finder = $this->getBinaryFinder();
		if ($finder === null) {
			return null;
		}
		foreach ($searchNames as $name) {
			$found = $finder->findBinaryPath($name);
			if (is_string($found) && $found !== '') {
				return $found;
			}
		}
		return null;
	}

	private function getBinaryFinder(): ?IBinaryFinder {
		if ($this->binaryFinder instanceof IBinaryFinder) {
			return $this->binaryFinder;
		}
		try {
			return Server::get(IBinaryFinder::class);
		} catch (\Throwable) {
			return null;
		}
	}

	private function configuredBinary(string $key): string {
		$value = $this->config->getSystemValue($key, '');
		return is_string($value) ? $value : '';
	}

	private function detectCpuCount(): int {
		if (is_readable('/proc/cpuinfo')) {
			return substr_count((string)file_get_contents('/proc/cpuinfo'), 'processor');
		}
		return 0;
	}

	private function getOptionalInt(string $key): ?int {
		$unset = new \stdClass();
		$value = $this->config->getSystemValue($key, $unset);
		if ($value === $unset || $value === null || $value === '') {
			return null;
		}
		return is_numeric($value) ? (int)$value : null;
	}

	private function setQuality(string $key, mixed $value, string $label): void {
		$quality = $this->toInt($value);
		if ($quality === null || $quality < 1 || $quality > 100) {
			throw new \InvalidArgumentException($label . ' quality must be between 1 and 100');
		}
		$this->appConfig->setValueInt('preview', $key, $quality);
	}

	private function setOptionalPositiveInt(string $key, mixed $value): void {
		if ($value === null || $value === '') {
			$this->config->deleteSystemValue($key);
			return;
		}
		$this->setIntKey($key, $value, 1);
	}

	private function setBinaryPath(string $key, mixed $value): void {
		$path = is_string($value) ? trim($value) : '';
		if ($path === '') {
			$this->config->deleteSystemValue($key);
			return;
		}
		if (str_contains($path, "\0") || strlen($path) > 4096) {
			throw new \InvalidArgumentException('Invalid binary path for ' . $key);
		}
		$this->config->setSystemValue($key, $path);
	}
}
