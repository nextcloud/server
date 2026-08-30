<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\IAppConfig;
use OCP\IBinaryFinder;
use OCP\IConfig;
use OCP\Server;

/**
 * Typed read/write helper for preview system and app config used by the admin UI.
 */
class PreviewAdminConfig {
	/** @var list<string> */
	private const IMAGICK_FORMATS = ['SVG', 'TIFF', 'PDF', 'AI', 'PSD', 'EPS', 'TTF', 'HEIC', 'TGA', 'SGI'];

	/**
	 * Full admin-table order from the last providers save in this request.
	 *
	 * @var list<string>|null
	 */
	private ?array $providerTableOrder = null;

	public function __construct(
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly ?IMagickSupport $imagickSupport = null,
		private readonly ?IBinaryFinder $binaryFinder = null,
	) {
	}

	/**
	 * Built-in defaults when ``enabledPreviewProviders`` is unset.
	 *
	 * @return list<class-string>
	 */
	public static function getBuiltinDefaultProviders(): array {
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
	 * Default enabledPreviewProviders list used when the key is unset.
	 *
	 * @return list<class-string>
	 */
	public static function getDefaultEnabledProviders(): array {
		return self::getBuiltinDefaultProviders();
	}

	/**
	 * Nextcloud-encouraged provider list.
	 *
	 * Matches core defaults, plus Imaginary first when it is configured (server
	 * tuning / AIO). Native HEIC is appended as a fallback because Imaginary
	 * does not handle every HEIC/HEIF file.
	 *
	 * @return list<class-string>
	 */
	public static function getRecommendedEnabledProviders(bool $imaginaryConfigured, bool $heicFallback = false): array {
		$providers = self::getBuiltinDefaultProviders();
		if (!$imaginaryConfigured) {
			return $providers;
		}
		$providers = array_merge([Imaginary::class], $providers);
		if ($heicFallback) {
			$providers[] = HEIC::class;
		}
		return array_values(array_unique($providers));
	}

	/**
	 * Providers disabled by default due to security, performance, or privacy
	 * concerns. They remain selectable, but Nextcloud discourages enabling
	 * them and considers them unsupported.
	 *
	 * Imaginary and the legacy Image helper are opt-in alternatives, not
	 * members of this list.
	 *
	 * @see https://docs.nextcloud.com/server/latest/admin_manual/configuration_files/previews_configuration.html
	 */
	public static function isUnsupportedProvider(string $class): bool {
		$class = self::normalizeClassName($class);
		if (in_array($class, self::getBuiltinDefaultProviders(), true)) {
			return false;
		}
		if (in_array($class, [Imaginary::class, ImaginaryPDF::class, Image::class], true)) {
			return false;
		}
		foreach (self::getProviderCatalog() as $entry) {
			if ($entry['class'] === $class) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Known core providers shown in the admin UI (enabled and disabled).
	 *
	 * @return list<array{class: class-string, name: string, mime: string, requirement: string, imagickFormat: ?string, sourceMimes: list<string>}>
	 */
	public static function getProviderCatalog(): array {
		return [
			self::catalogEntry(PNG::class, 'PNG', 'image/png', 'none', null, ['image/png']),
			self::catalogEntry(JPEG::class, 'JPEG', 'image/jpeg', 'none', null, ['image/jpeg']),
			self::catalogEntry(GIF::class, 'GIF', 'image/gif', 'none', null, ['image/gif']),
			self::catalogEntry(BMP::class, 'BMP', 'image/bmp', 'none', null, ['image/bmp']),
			self::catalogEntry(XBitmap::class, 'XBitmap', 'image/x-xbitmap', 'none', null, ['image/x-xbitmap']),
			self::catalogEntry(WebP::class, 'WebP', 'image/webp', 'none', null, ['image/webp']),
			self::catalogEntry(Krita::class, 'Krita', 'application/x-krita', 'none', null, ['application/x-krita']),
			self::catalogEntry(HEIC::class, 'HEIC', 'image/heic, image/heif', 'imagick', 'HEIC', ['image/heic', 'image/heif']),
			self::catalogEntry(TIFF::class, 'TIFF', 'image/tiff', 'imagick', 'TIFF', ['image/tiff']),
			self::catalogEntry(SVG::class, 'SVG', 'image/svg+xml', 'imagick', 'SVG', ['image/svg+xml']),
			self::catalogEntry(TGA::class, 'TGA', 'image/tga', 'imagick', 'TGA', ['image/tga']),
			self::catalogEntry(SGI::class, 'SGI', 'image/sgi', 'imagick', 'SGI', ['image/sgi']),
			self::catalogEntry(Imaginary::class, 'Imaginary', 'images (bmp, png, jpeg, gif, heic, heif, svg, tiff, webp), illustrator', 'imaginary', null, ['image/bmp', 'image/x-bitmap', 'image/png', 'image/jpeg', 'image/gif', 'image/heic', 'image/heif', 'image/svg+xml', 'image/tiff', 'image/webp', 'application/illustrator']),
			self::catalogEntry(ImaginaryPDF::class, 'Imaginary PDF', 'application/pdf', 'imaginary', null, ['application/pdf']),
			self::catalogEntry(PDF::class, 'PDF', 'application/pdf', 'imagick', 'PDF', ['application/pdf']),
			self::catalogEntry(Postscript::class, 'Postscript', 'application/postscript', 'imagick', 'EPS', ['application/postscript']),
			self::catalogEntry(Illustrator::class, 'Illustrator', 'application/illustrator', 'imagick', 'AI', ['application/illustrator']),
			self::catalogEntry(Photoshop::class, 'Photoshop', 'application/x-photoshop', 'imagick', 'PSD', ['application/x-photoshop']),
			self::catalogEntry(Font::class, 'Font', 'application/font-sfnt', 'imagick', 'TTF', ['application/font-sfnt']),
			self::catalogEntry(MarkDown::class, 'Markdown', 'text/markdown', 'none', null, ['text/markdown']),
			self::catalogEntry(TXT::class, 'Plain text', 'text/plain', 'none', null, ['text/plain']),
			self::catalogEntry(OpenDocument::class, 'OpenDocument', 'application/vnd.oasis.opendocument.*', 'none', null, ['application/vnd.oasis.opendocument.*']),
			self::catalogEntry(MSOfficeDoc::class, 'MS Office Doc', 'application/msword', 'office', null, ['application/msword']),
			self::catalogEntry(MSOffice2003::class, 'MS Office 2003', 'application/vnd.ms-*', 'office', null, ['application/vnd.ms-*']),
			self::catalogEntry(MSOffice2007::class, 'MS Office 2007', 'application/vnd.openxmlformats-officedocument.*', 'office', null, ['application/vnd.openxmlformats-officedocument.*']),
			self::catalogEntry(StarOffice::class, 'StarOffice', 'application/vnd.sun.xml.*', 'office', null, ['application/vnd.sun.xml.*']),
			self::catalogEntry(EMF::class, 'EMF', 'image/emf', 'office', null, ['image/emf']),
			self::catalogEntry(MP3::class, 'MP3', 'audio/mpeg', 'none', null, ['audio/mpeg']),
			self::catalogEntry(Movie::class, 'Movie', 'video/*', 'ffmpeg', null, ['video/*']),
			self::catalogEntry(Image::class, 'Image (legacy)', 'enables PNG, JPEG, GIF, BMP, XBitmap, Krita, WebP', 'none', null, ['image/png', 'image/jpeg', 'image/gif', 'image/bmp', 'image/x-xbitmap', 'application/x-krita', 'image/webp']),
		];
	}

	/**
	 * @param class-string $class
	 * @param list<string> $sourceMimes
	 * @return array{class: class-string, name: string, mime: string, requirement: string, imagickFormat: ?string, sourceMimes: list<string>}
	 */
	private static function catalogEntry(
		string $class,
		string $name,
		string $mime,
		string $requirement,
		?string $imagickFormat,
		array $sourceMimes,
	): array {
		return [
			'class' => $class,
			'name' => $name,
			'mime' => $mime,
			'requirement' => $requirement,
			'imagickFormat' => $imagickFormat,
			'sourceMimes' => $sourceMimes,
		];
	}

	/**
	 * @return list<string>
	 */
	public function getEnabledPreviewProviders(): array {
		$value = $this->config->getSystemValue('enabledPreviewProviders', null);
		if (!is_array($value)) {
			return $this->getRecommendedEnabledProvidersForInstance();
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

		$order = [];
		$seen = [];
		foreach ($this->providerTableOrder ?? $this->getDefaultProviderOrder() as $class) {
			$class = self::normalizeClassName((string)$class);
			if ($class === '' || isset($seen[$class])) {
				continue;
			}
			$order[] = $class;
			$seen[$class] = true;
		}
		foreach ($enabled as $class) {
			if (isset($seen[$class])) {
				continue;
			}
			$order[] = $class;
			$seen[$class] = true;
		}
		foreach (self::getProviderCatalog() as $entry) {
			if (isset($seen[$entry['class']])) {
				continue;
			}
			$order[] = $entry['class'];
			$seen[$entry['class']] = true;
		}

		$providers = [];
		foreach ($order as $class) {
			$meta = $this->findCatalogEntry($class) ?? [
				'class' => $class,
				'name' => $this->classBasename($class),
				'mime' => '',
				'requirement' => 'none',
				'imagickFormat' => null,
				'sourceMimes' => [],
			];
			$providers[] = $this->providerRow($meta, isset($enabledSet[$class]), $detection);
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
			'defaultEnabledProviders' => $this->getRecommendedEnabledProvidersForInstance(),
			'defaultProviderOrder' => $this->getDefaultProviderOrder(),
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
		$detection = $this->getDetection();
		$enabled = [];
		$tableOrder = [];
		foreach ($rows as $row) {
			if (!is_array($row) || !isset($row['class']) || !is_string($row['class'])) {
				continue;
			}
			$class = self::normalizeClassName($row['class']);
			if ($class !== '') {
				$tableOrder[] = $class;
			}
			if (!($row['enabled'] ?? false)) {
				continue;
			}
			$entry = $this->findCatalogEntry($class);
			if ($entry !== null) {
				$requirement = $entry['requirement'] ?? 'none';
				$format = $entry['imagickFormat'] ?? null;
				if (!$this->isProviderAvailable($requirement, is_string($format) ? $format : null, $detection)) {
					continue;
				}
			}
			$enabled[] = $class;
		}
		$this->providerTableOrder = array_values(array_unique($tableOrder));
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
	 * @return array{class: class-string, name: string, mime: string, requirement: string, imagickFormat: ?string, sourceMimes: list<string>}|null
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
	 * @param array{class: string, name: string, mime: string, requirement?: string, imagickFormat?: ?string, sourceMimes?: list<string>} $entry
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
			'sourceMimes' => $entry['sourceMimes'] ?? [],
			'enabled' => $enabled,
			'requirement' => $requirement,
			'imagickFormat' => $format,
			'available' => $this->isProviderAvailable($requirement, is_string($format) ? $format : null, $detection),
			'unsupported' => self::isUnsupportedProvider($entry['class']),
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
		$imagickSupport = $this->imagickSupport;
		$imagick = $imagickSupport !== null && $imagickSupport->hasExtension();
		$formats = [];
		foreach (self::IMAGICK_FORMATS as $format) {
			$formats[$format] = $imagick && $imagickSupport->supportsFormat($format);
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
	 * @return list<class-string>
	 */
	private function getRecommendedEnabledProvidersForInstance(): array {
		$detection = $this->getDetection();
		return self::getRecommendedEnabledProviders(
			(bool)$detection['imaginaryConfigured'],
			(bool)($detection['imagickFormats']['HEIC'] ?? false),
		);
	}

	/**
	 * Canonical provider list order for “Reset to defaults”: recommended
	 * enabled providers first, then the rest of the catalog.
	 *
	 * @return list<class-string>
	 */
	private function getDefaultProviderOrder(): array {
		/** @var list<class-string> $order */
		$order = $this->getRecommendedEnabledProvidersForInstance();
		foreach (self::getProviderCatalog() as $entry) {
			if (!in_array($entry['class'], $order, true)) {
				$order[] = $entry['class'];
			}
		}
		return $order;
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
