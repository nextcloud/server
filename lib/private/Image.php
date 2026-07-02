<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC;

use finfo;
use GdImage;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IImage;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * GD-backed image helper for loading, transforming, and exporting images.
 *
 * Supports file, stream, raw-data, and base64 inputs, along with common operations
 * such as resizing, cropping, and EXIF-based orientation handling.
 */
class Image implements IImage {
	// Default memory limit for images to load (256 MB).
	protected const DEFAULT_MEMORY_LIMIT = 256;

	// Default quality for jpeg images
	protected const DEFAULT_JPEG_QUALITY = 80;

	// Default quality for webp images
	protected const DEFAULT_WEBP_QUALITY = 80;

	// Loaded image resource; false until an image is loaded.
	protected GdImage|false $resource = false;

	// Output/input type defaults to PNG when no specific type is known.
	protected int $imageType = IMAGETYPE_PNG;
	protected ?string $mimeType = 'image/png';

	// Source file path for loaded images, if available.
	protected ?string $filePath = null;

	// Cached metadata.
	private ?array $exif = null;
	private ?finfo $fileInfo = null;

	private readonly LoggerInterface $logger;
	private readonly IAppConfig $appConfig;
	private readonly IConfig $config;

	public function __construct(
		?LoggerInterface $logger = null,
		?IAppConfig $appConfig = null,
		?IConfig $config = null,
	) {
		// This class is typically instantiated directly as a short-lived utility object
		// without any constructor arguments.
		$this->logger = $logger ?? Server::get(LoggerInterface::class);
		$this->appConfig = $appConfig ?? Server::get(IAppConfig::class);
		$this->config = $config ?? Server::get(IConfig::class);

		// Optional MIME detection support for image data loaded from strings.
		if (class_exists(finfo::class)) {
			$this->fileInfo = new finfo(FILEINFO_MIME_TYPE);
		}
	}

	/**
	 * @psalm-assert-if-true \GdImage $this->resource
	 */
	#[\Override]
	public function valid(): bool {
		if (is_object($this->resource) && get_class($this->resource) === \GdImage::class) {
			return true;
		}

		return false;
	}

	#[\Override]
	public function mimeType(): ?string {
		return $this->valid() ? $this->mimeType : null;
	}

	#[\Override]
	public function width(): int {
		return $this->valid() ? imagesx($this->resource) : -1;
	}

	#[\Override]
	public function height(): int {
		return $this->valid() ? imagesy($this->resource) : -1;
	}

	#[\Override]
	public function widthTopLeft(): int {
		$orientation = $this->getOrientation();
		$this->logger->debug('Image->widthTopLeft() Orientation: ' . $orientation, ['app' => 'core']);
		switch ($orientation) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->width();
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->height();
		}

		return $this->width();
	}

	#[\Override]
	public function heightTopLeft(): int {
		$orientation = $this->getOrientation();
		$this->logger->debug('Image->heightTopLeft() Orientation: ' . $orientation, ['app' => 'core']);
		switch ($orientation) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->height();
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->width();
		}

		return $this->height();
	}

	#[\Override]
	public function show(?string $mimeType = null): bool {
		$mimeType ??= $this->mimeType();

		if ($mimeType !== null) {
			header('Content-Type: ' . $mimeType);
		}

		return $this->_output(null, $mimeType);
	}

	#[\Override]
	public function save(?string $filePath = null, ?string $mimeType = null): bool {
		$mimeType ??= $this->mimeType();
		$filePath ??= $this->filePath;

		if ($filePath === null) {
			$this->logger->error(__METHOD__ . '(): called with no path.', ['app' => 'core']);
			return false;
		}

		return $this->_output($filePath, $mimeType);
	}

	/**
	 * Writes the current image to a file or directly to output.
	 *
	 * Encodes the image using the provided MIME type, or falls back to the current
	 * image type. If the resolved image type is unsupported, PNG encoding is used.
	 * If $filePath is null or empty, the encoded image is written directly to output.
	 *
	 * @param string|null $filePath Destination file path, or null/empty to write directly to output.
	 * @param string|null $mimeType MIME type to force for encoding, or null to use the current image type.
	 * @return bool True on success, false if no valid image is loaded or the destination is not writable.
	 * @throws \Exception If a forced MIME type is unsupported or the required GD output function is unavailable.
	 */
	private function _output(?string $filePath = null, ?string $mimeType = null): bool {
		if ($filePath !== null && $filePath !== '') {
			$directory = dirname($filePath);

			if (!file_exists($directory)) {
				mkdir(dirname($filePath), 0777, true);
			}

			if (!is_writable($directory)) {
				$this->logger->error(__METHOD__ . '(): Directory \'' . $directory . '\' is not writable.', ['app' => 'core']);
				return false;
			}

			if (file_exists($filePath) && !is_writable($filePath)) {
				$this->logger->error(__METHOD__ . '(): File \'' . $filePath . '\' is not writable.', ['app' => 'core']);
				return false;
			}
		}

		if (!$this->valid()) {
			return false;
		}

		$imageType = $this->imageType;
		if ($mimeType !== null) {
			$imageType = match ($mimeType) {
				'image/gif' => IMAGETYPE_GIF,
				'image/jpeg' => IMAGETYPE_JPEG,
				'image/png' => IMAGETYPE_PNG,
				'image/x-xbitmap' => IMAGETYPE_XBM,
				'image/bmp', 'image/x-ms-bmp' => IMAGETYPE_BMP,
				'image/webp' => IMAGETYPE_WEBP,
				default => throw new \Exception('Image::_output(): "' . $mimeType . '" is not supported when forcing a specific output format'),
			};
		}

		return match ($imageType) {
			IMAGETYPE_GIF => imagegif($this->resource, $filePath),
			IMAGETYPE_JPEG => (function () use ($filePath): bool {
				imageinterlace($this->resource, true);
				return imagejpeg($this->resource, $filePath, $this->getJpegQuality());
			})(),
			IMAGETYPE_PNG => imagepng($this->resource, $filePath),
			IMAGETYPE_XBM => function_exists('imagexbm')
				? imagexbm($this->resource, $filePath)
				: throw new \Exception('Image::_output(): imagexbm() is not supported.'),
			IMAGETYPE_WBMP => imagewbmp($this->resource, $filePath),
			IMAGETYPE_BMP => imagebmp($this->resource, $filePath),
			IMAGETYPE_WEBP => imagewebp($this->resource, $filePath, $this->getWebpQuality()),
			default => imagepng($this->resource, $filePath),
		};
	}

	/**
	 * Prints the image when called as $image().
	 */
	public function __invoke() {
		return $this->show();
	}

	public function setResource(\GdImage $resource): void {
		$this->resource = $resource;
	}

	/**
	 * @return false|\GdImage Returns the image resource if any
	 */
	#[\Override]
	public function resource() {
		return $this->resource;
	}

	#[\Override]
	public function dataMimeType(): ?string {
		if (!$this->valid()) {
			return null;
		}

		switch ($this->mimeType) {
			case 'image/png':
			case 'image/jpeg':
			case 'image/gif':
			case 'image/webp':
				return $this->mimeType;
			default:
				return 'image/png';
		}
	}

	#[\Override]
	public function data(): ?string {
		if (!$this->valid()) {
			return null;
		}

		ob_start();

		switch ($this->mimeType) {
			case 'image/png':
				$result = imagepng($this->resource);
				break;

			case 'image/jpeg':
				imageinterlace($this->resource, true);
				$result = imagejpeg($this->resource, null, $this->getJpegQuality());
				break;

			case 'image/gif':
				$result = imagegif($this->resource);
				break;

			case 'image/webp':
				$result = imagewebp($this->resource, null, $this->getWebpQuality());
				break;

			default:
				$this->logger->info('Image->data. Could not guess mime-type, defaulting to png', ['app' => 'core']);
				$result = imagepng($this->resource);
				break;
		}

		if (!$result) {
			$this->logger->error('Image->data. Error getting image data.', ['app' => 'core']);
		}

		return ob_get_clean();
	}

	/**
	 * @return string Base64 encoded image data suitable for VCard embedding, or an empty string on failure.
	 */
	public function __toString(): string {
		$data = $this->data();
		return $data !== null ? base64_encode($data) : '';
	}

	protected function getJpegQuality(): int {
		$quality = $this->appConfig->getValueInt('preview', 'jpeg_quality', self::DEFAULT_JPEG_QUALITY);
		return min(100, max(10, $quality));
	}

	protected function getWebpQuality(): int {
		$quality = $this->appConfig->getValueInt('preview', 'webp_quality', self::DEFAULT_WEBP_QUALITY);
		return min(100, max(10, $quality));
	}

	private function isValidExifData(array $exif): bool {
		return isset($exif['Orientation']) && is_numeric($exif['Orientation']);
	}

	#[\Override]
	public function getOrientation(): int {
		if ($this->exif !== null) {
			return (int)$this->exif['Orientation'];
		}

		if ($this->imageType !== IMAGETYPE_JPEG) {
			$this->logger->debug('Image->getOrientation() Image is not a JPEG.', ['app' => 'core']);
			return -1;
		}

		if (!is_callable('exif_read_data')) {
			$this->logger->debug('Image->getOrientation() Exif module not enabled.', ['app' => 'core']);
			return -1;
		}

		if (!$this->valid()) {
			$this->logger->debug('Image->getOrientation() No image loaded.', ['app' => 'core']);
			return -1;
		}

		if (is_null($this->filePath) || !is_readable($this->filePath)) {
			$this->logger->debug('Image->getOrientation() No readable file path set.', ['app' => 'core']);
			return -1;
		}

		$exif = @exif_read_data($this->filePath, 'IFD0');
		if ($exif === false || !$this->isValidExifData($exif)) {
			return -1;
		}
		$this->exif = $exif;

		return (int)$exif['Orientation'];
	}

	#[\Override]
	public function readExif(string $data): void {
		if (!is_callable('exif_read_data')) {
			$this->logger->debug('Image->readExif() Exif module not enabled.', ['app' => 'core']);
			return;
		}

		if (!$this->valid()) {
			$this->logger->debug('Image->readExif() No image loaded.', ['app' => 'core']);
			return;
		}

		$exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($data));
		if ($exif === false || !$this->isValidExifData($exif)) {
			return;
		}

		$this->exif = $exif;
	}

	#[\Override]
	public function fixOrientation(): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$orientation = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $orientation, ['app' => 'core']);

		[$rotate, $flip] = match ($orientation) {
			-1, 1 => [0, false],
			2 => [0, true],
			3 => [180, false],
			4 => [180, true],
			5 => [90, true],
			6 => [270, false],
			7 => [270, true],
			8 => [90, false],
			default => [0, false],
		};

		// Nothing to fix
		if ($rotate === 0 && !$flip) {
			return false;
		}

		if ($flip && function_exists('imageflip')) {
			imageflip($this->resource, IMG_FLIP_HORIZONTAL);
		}

		// Nothing else left to do
		if ($rotate === 0) {
			return true;
		}

		$rotated = imagerotate($this->resource, $rotate, 0);
		if ($rotated === false) {
			$this->logger->debug(__METHOD__ . '() Error during orientation fixing', ['app' => 'core']);
			return false;
		}

		if (!imagealphablending($rotated, true)) {
			$this->logger->debug(__METHOD__ . '() Error during alpha-blending', ['app' => 'core']);
			return false;
		}

		if (!imagesavealpha($rotated, true)) {
			$this->logger->debug(__METHOD__ . '() Error during alpha-saving', ['app' => 'core']);
			return false;
		}

		$this->resource = $rotated;
		return true;
	}

	/**
	 * Loads an image from an open file handle.
	 *
	 * It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	 *
	 * @param resource $handle
	 * @return \GdImage|false An image resource or false on error
	 *
	 * @internal
	 */
	public function loadFromFileHandle($handle) {
		$contents = stream_get_contents($handle);
		if ($this->loadFromData($contents)) {
			return $this->resource;
		}

		return false;
	}

	/**
	 * Check if allocating an image with the given size is allowed.
	 */
	private function checkImageMemory(int $width, int $height): bool {
		$memory_limit = $this->config->getSystemValueInt('preview_max_memory', self::DEFAULT_MEMORY_LIMIT);
		if ($memory_limit < 0) {
			// Not limited.
			return true;
		}

		// Assume 32 bits per pixel.
		if ($width * $height * 4 > $memory_limit * 1024 * 1024) {
			$this->logger->info('Image size of ' . $width . 'x' . $height . ' would exceed allowed memory limit of ' . $memory_limit . '. You may increase the preview_max_memory in your config.php if you need previews of this image.');
			return false;
		}

		return true;
	}

	/**
	 * Check if loading an image file from the given path is allowed.
	 */
	private function checkImageSize(string $path): bool {
		$size = @getimagesize($path);
		if (!$size) {
			return false;
		}

		$width = $size[0];
		$height = $size[1];
		if (!$this->checkImageMemory($width, $height)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if loading an image from the given string is allowed.
	 *
	 * @param string $data A string of image data as read from a file.
	 */
	private function checkImageDataSize(string $data): bool {
		$size = @getimagesizefromstring($data);
		if (!$size) {
			return false;
		}

		$width = $size[0];
		$height = $size[1];
		if (!$this->checkImageMemory($width, $height)) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if specified image is an animated WebP image.
	 *
	 * Check for animated header before generating preview since libgd does not handle them well
	 * Adapted from here: https://stackoverflow.com/a/68491679/4085517 (stripped to only to check for animations + added additional error checking)
	 * Header format details here: https://developers.google.com/speed/webp/docs/riff_container
	 */
	private function isAnimatedWebp(string $imagePath): bool {
		$fp = fopen($imagePath, 'rb');
		if (!$fp) {
			return true;
		}

		$data = fread($fp, 90);
		fclose($fp);

		if ($data === false) {
			return true;
		}

		// Load up the header data, if any
		$header = unpack(
			'A4Riff/I1Filesize/A4Webp/A4Vp/A74Chunk',
			$data
		);

		if ($header === false) {
			return true;
		}

		// Check if we're really dealing with a valid WEBP header rather than just one suffixed ".webp"
		if (!isset($header['Riff']) || strtoupper($header['Riff']) !== 'RIFF') {
			return true;
		}

		if (!isset($header['Webp']) || strtoupper($header['Webp']) !== 'WEBP') {
			return true;
		}

		if (!isset($header['Vp']) || strpos(strtoupper($header['Vp']), 'VP8') === false) {
			return true;
		}

		// Check for animation indicators
		return strpos(strtoupper($header['Chunk']), 'ANIM') !== false
			|| strpos(strtoupper($header['Chunk']), 'ANMF') !== false;
	}

	/**
	 * Loads an image from a local file.
	 *
	 * @param bool|string $imagePath The path to a local file.
	 * @return \GdImage|false An image resource or false on error
	 *
	 * @internal
	 */
	public function loadFromFile(string|bool $imagePath = false): GdImage|false {
		if (
			is_bool($imagePath) // FIXME: drop this?
			|| !@is_file($imagePath)
			|| !file_exists($imagePath)
			|| filesize($imagePath) < 12 // exif_imagetype throws "read error!" if file is less than 12 byte
			|| !is_readable($imagePath)
		) {
			return false;
		}

		$imageType = exif_imagetype($imagePath);
		switch ($imageType) {
			case IMAGETYPE_GIF:
				if (!(imagetypes() & IMG_GIF)) {
					$this->logger->debug('Image->loadFromFile, GIF images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = imagecreatefromgif($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, GIF image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				// Preserve transparency
				imagealphablending($this->resource, true);
				imagesavealpha($this->resource, true);
				break;

			case IMAGETYPE_JPEG:
				if (!(imagetypes() & IMG_JPG)) {
					$this->logger->debug('Image->loadFromFile, JPG images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}
					
				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = @imagecreatefromjpeg($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, JPG image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				break;

			case IMAGETYPE_PNG:
				if (!(imagetypes() & IMG_PNG)) {
					$this->logger->debug('Image->loadFromFile, PNG images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = @imagecreatefrompng($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, PNG image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				// Preserve transparency
				imagealphablending($this->resource, true);
				imagesavealpha($this->resource, true);
				break;

			case IMAGETYPE_XBM:
				if (!(imagetypes() & IMG_XPM)) {
					$this->logger->debug('Image->loadFromFile, XBM/XPM images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				
				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = @imagecreatefromxbm($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, XBM/XPM image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				break;

			case IMAGETYPE_WBMP:
				if (!(imagetypes() & IMG_WBMP)) {
					$this->logger->debug('Image->loadFromFile, WBMP images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = @imagecreatefromwbmp($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, WBMP image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				break;

			case IMAGETYPE_BMP:
				if (!(imagetypes() & IMG_BMP)) {
					$this->logger->debug('Image->loadFromFile, BMP images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				$this->resource = imagecreatefrombmp($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, BMP image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				break;

			case IMAGETYPE_WEBP:
				if (!(imagetypes() & IMG_WEBP)) {
					$this->logger->debug('Image->loadFromFile, WEBP images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				if (!$this->checkImageSize($imagePath)) {
					return false;
				}

				// Animated (or not really WebP) so don't let it reach libgd
				if ($this->isAnimatedWebp($imagePath)) {
					$this->logger->debug('Image->loadFromFile, animated WEBP images not supported: ' . $imagePath, ['app' => 'core']);
					return false;
				}

				// We're safe so give it to libgd
				$this->resource = @imagecreatefromwebp($imagePath);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, WEBP image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				break;

			/*
			case IMAGETYPE_TIFF_II: // (intel byte order)
				break;
			case IMAGETYPE_TIFF_MM: // (motorola byte order)
				break;
			case IMAGETYPE_JPC:
				break;
			case IMAGETYPE_JP2:
				break;
			case IMAGETYPE_JPX:
				break;
			case IMAGETYPE_JB2:
				break;
			case IMAGETYPE_SWC:
				break;
			case IMAGETYPE_IFF:
				break;
			case IMAGETYPE_ICO:
				break;
			case IMAGETYPE_SWF:
				break;
			case IMAGETYPE_PSD:
				break;
			*/

			default:
				// this is mostly file created from encrypted file
				$data = file_get_contents($imagePath);

				if (!$this->checkImageDataSize($data)) {
					return false;
				}

				$this->resource = @imagecreatefromstring($data);
				if (!$this->resource) {
					$this->logger->debug('Image->loadFromFile, (Default/unknown) image not valid: ' . $imagePath, ['app' => 'core']);
					return false;
				}
				$imageType = IMAGETYPE_PNG;
				$this->logger->debug('Image->loadFromFile, Default', ['app' => 'core']);
				break;
		}

		if (!($this->valid())) {
			return false;
		}
		
		$this->imageType = $imageType;
		$this->mimeType = image_type_to_mime_type($imageType);
		$this->filePath = $imagePath;

		return $this->resource;
	}

	#[\Override]
	public function loadFromData(string $str): GdImage|false {
		if (!$this->checkImageDataSize($str)) {
			return false;
		}

		$this->resource = @imagecreatefromstring($str);
		if (!$this->resource || !$this->valid()) {
			$this->logger->debug('Image->loadFromData, could not load', ['app' => 'core']);
			return false;
		}

		if ($this->fileInfo) {
			$this->mimeType = $this->fileInfo->buffer($str);
		}

		imagealphablending($this->resource, false);
		imagesavealpha($this->resource, true);

		return $this->resource;
	}

	/**
	 * Loads an image from a base64 encoded string.
	 *
	 * @param string $str A base64 encoded string of image data
	 * @return \GdImage|false An image resource or false on error
	 *
	 * @internal
	 */
	public function loadFromBase64(string $str): GdImage|false {
		$data = base64_decode($str, true);
		if ($data === false) {
			return false;
		}

		// try to load from string data
		return $this->loadFromData($data);
	}

	#[\Override]
	public function resize(int $maxSize): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$result = $this->resizeNew($maxSize);
		$this->resource = $result;

		return $this->valid();
	}

	private function resizeNew(int $maxSize): \GdImage|false {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$ratioOrig = $widthOrig / $heightOrig;

		if ($ratioOrig > 1) {
			$newHeight = round($maxSize / $ratioOrig);
			$newWidth = $maxSize;
		} else {
			$newWidth = round($maxSize * $ratioOrig);
			$newHeight = $maxSize;
		}

		return $this->preciseResizeNew((int)round($newWidth), (int)round($newHeight));
	}

	#[\Override]
	public function preciseResize(int $width, int $height): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$result = $this->preciseResizeNew($width, $height);
		$this->resource = $result;

		return $this->valid();
	}

	/**
	 * @internal
	 */
	public function preciseResizeNew(int $width, int $height): \GdImage|false {
		if (!($width > 0) || !($height > 0)) {
			$this->logger->info(__METHOD__ . '(): Requested image size not bigger than 0', ['app' => 'core']);
			return false;
		}

		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$process = imagecreatetruecolor($width, $height);
		if ($process === false) {
			$this->logger->debug(__METHOD__ . '(): Error creating true color image', ['app' => 'core']);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF || $this->imageType === IMAGETYPE_PNG) {
			$alpha = imagecolorallocatealpha($process, 0, 0, 0, 127);
			if ($alpha === false) {
				$alpha = null;
			}
			imagecolortransparent($process, $alpha);
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$res = imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $width, $height, $widthOrig, $heightOrig);
		if ($res === false) {
			$this->logger->debug(__METHOD__ . '(): Error re-sampling process image', ['app' => 'core']);
			return false;
		}

		return $process;
	}

	#[\Override]
	public function centerCrop(int $size = 0): bool {
		if (!$this->valid()) {
			$this->logger->debug('Image->centerCrop, No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		if ($widthOrig === $heightOrig && $size == 0) {
			return true;
		}

		$ratioOrig = $widthOrig / $heightOrig;
		$width = $height = min($widthOrig, $heightOrig);
		if ($ratioOrig > 1) {
			$x = (int)(($widthOrig / 2) - ($width / 2));
			$y = 0;
		} else {
			$y = (int)(($heightOrig / 2) - ($height / 2));
			$x = 0;
		}

		if ($size > 0) {
			$targetWidth = $size;
			$targetHeight = $size;
		} else {
			$targetWidth = $width;
			$targetHeight = $height;
		}
		$process = imagecreatetruecolor($targetWidth, $targetHeight);
		if ($process === false) {
			$this->logger->debug('Image->centerCrop, Error creating true color image', ['app' => 'core']);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF || $this->imageType === IMAGETYPE_PNG) {
			$alpha = imagecolorallocatealpha($process, 0, 0, 0, 127);
			if ($alpha === false) {
				$alpha = null;
			}
			imagecolortransparent($process, $alpha);
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$result = imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);
		if ($result === false) {
			$this->logger->debug('Image->centerCrop, Error re-sampling process image ' . $width . 'x' . $height, ['app' => 'core']);
			return false;
		}
		$this->resource = $process;

		return true;
	}

	#[\Override]
	public function crop(int $x, int $y, int $w, int $h): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$result = $this->cropNew($x, $y, $w, $h);
		$this->resource = $result;

		return $this->valid();
	}

	/**
	 * Creates and returns a new raw GD image containing the cropped region
	 * from the currently loaded image.
	 *
	 * Does not mutate the current image.
	 *
	 * @param int $x Horizontal position of the top-left corner of the crop area within the current image
	 * @param int $y Vertical position of the top-left corner of the crop area within the current image
	 * @param int $w Width of the cropped area
	 * @param int $h Height of the cropped area
	 * @return GdImage|false A new cropped image, or false on failure
	 *
	 * @internal
	 */
	public function cropNew(int $x, int $y, int $w, int $h): GdImage|false {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$process = imagecreatetruecolor($w, $h);
		if ($process === false) {
			$this->logger->debug(__METHOD__ . '(): Error creating true color image', ['app' => 'core']);
			return false;
		}

		// preserve transparency
		if ($this->imageType === IMAGETYPE_GIF || $this->imageType === IMAGETYPE_PNG) {
			$alpha = imagecolorallocatealpha($process, 0, 0, 0, 127);
			if ($alpha === false) {
				$alpha = null;
			}
			imagecolortransparent($process, $alpha);
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		$result = imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($result === false) {
			$this->logger->debug(__METHOD__ . '(): Error re-sampling process image ' . $w . 'x' . $h, ['app' => 'core']);
			return false;
		}

		return $process;
	}

	#[\Override]
	public function fitIn(int $maxWidth, int $maxHeight): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);
		$ratio = $widthOrig / $heightOrig;

		$newWidth = min($maxWidth, $ratio * $maxHeight);
		$newHeight = min($maxHeight, $maxWidth / $ratio);

		$this->preciseResize((int)round($newWidth), (int)round($newHeight));

		return true;
	}

	#[\Override]
	public function scaleDownToFit(int $maxWidth, int $maxHeight): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = imagesx($this->resource);
		$heightOrig = imagesy($this->resource);

		if ($widthOrig > $maxWidth || $heightOrig > $maxHeight) {
			return $this->fitIn($maxWidth, $maxHeight);
		}

		return false;
	}

	#[\Override]
	public function copy(): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);

		if (!$this->valid()) {
			/* image is invalid, return an empty one */
			return $image;
		}

		$image->resource = imagecreatetruecolor($this->width(), $this->height());
		if (!$image->valid()) {
			/* image creation failed, cannot copy in it */
			return $image;
		}

		imagecopy(
			$image->resource,
			$this->resource,
			0,
			0,
			0,
			0,
			$this->width(),
			$this->height()
		);

		return $image;
	}

	#[\Override]
	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->cropNew($x, $y, $w, $h);

		return $image;
	}

	#[\Override]
	public function preciseResizeCopy(int $width, int $height): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->preciseResizeNew($width, $height);

		return $image;
	}

	#[\Override]
	public function resizeCopy(int $maxSize): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->resource = $this->resizeNew($maxSize);

		return $image;
	}

	/**
	 * Destroys the current image and resets the object
	 */
	public function destroy(): void {
		$this->resource = false;
	}

	public function __destruct() {
		$this->destroy();
	}
}

if (!function_exists('exif_imagetype')) {
	/**
	 * Workaround if exif_imagetype does not exist
	 *
	 * @link https://www.php.net/manual/en/function.exif-imagetype.php#80383
	 * @param string $fileName
	 * @return int|false
	 */
	function exif_imagetype(string $fileName): int|false {
		if (($info = getimagesize($fileName)) !== false) {
			return $info[2];
		}

		return false;
	}
}
