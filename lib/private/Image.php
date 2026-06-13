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
 * Class for basic image manipulation
 */
class Image implements IImage {
	// Default memory limit for images to load (256 MBytes).
	protected const DEFAULT_MEMORY_LIMIT = 256;

	// Default quality for jpeg images
	protected const DEFAULT_JPEG_QUALITY = 80;

	// Default quality for webp images
	protected const DEFAULT_WEBP_QUALITY = 80;

	// ICC profile marker in JPEG APP2 segments
	private const JPEG_ICC_IDENTIFIER = "ICC_PROFILE\x00";

	// Max ICC bytes per APP2 segment: 0xFFFF - 2 (length) - 12 (marker) - 2 (index and count)
	private const JPEG_ICC_MAX_CHUNK_SIZE = 65519;

	// ICC profiles precede the image data, so the scan can stop early
	private const ICC_SCAN_BYTE_LIMIT = 8 * 1024 * 1024;

	// tmp resource.
	protected GdImage|false $resource = false;
	// Default to png if file type isn't evident.
	protected int $imageType = IMAGETYPE_PNG;
	// Default to png
	protected ?string $mimeType = 'image/png';
	protected ?string $filePath = null;
	private ?finfo $fileInfo = null;
	private LoggerInterface $logger;
	private IAppConfig $appConfig;
	private IConfig $config;
	private ?array $exif = null;
	// Colour profile carried from the source into generated output
	private ?string $iccProfile = null;

	/**
	 * @throws \InvalidArgumentException in case the $imageRef parameter is not null
	 */
	public function __construct(
		?LoggerInterface $logger = null,
		?IAppConfig $appConfig = null,
		?IConfig $config = null,
	) {
		$this->logger = $logger ?? Server::get(LoggerInterface::class);
		$this->appConfig = $appConfig ?? Server::get(IAppConfig::class);
		$this->config = $config ?? Server::get(IConfig::class);

		if (class_exists(finfo::class)) {
			$this->fileInfo = new finfo(FILEINFO_MIME_TYPE);
		}
	}

	/**
	 * Determine whether the object contains an image resource.
	 *
	 * @psalm-assert-if-true \GdImage $this->resource
	 * @return bool
	 */
	#[\Override]
	public function valid(): bool {
		if (is_object($this->resource) && get_class($this->resource) === \GdImage::class) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the MIME type of the image or null if no image is loaded.
	 *
	 * @return string
	 */
	#[\Override]
	public function mimeType(): ?string {
		return $this->valid() ? $this->mimeType : null;
	}

	/**
	 * Returns the width of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	#[\Override]
	public function width(): int {
		if ($this->valid()) {
			return imagesx($this->resource);
		}
		return -1;
	}

	/**
	 * Returns the height of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	#[\Override]
	public function height(): int {
		if ($this->valid()) {
			return imagesy($this->resource);
		}
		return -1;
	}

	/**
	 * Returns the width when the image orientation is top-left.
	 *
	 * @return int
	 */
	#[\Override]
	public function widthTopLeft(): int {
		$o = $this->getOrientation();
		$this->logger->debug('Image->widthTopLeft() Orientation: ' . $o, ['app' => 'core']);
		switch ($o) {
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

	/**
	 * Returns the height when the image orientation is top-left.
	 *
	 * @return int
	 */
	#[\Override]
	public function heightTopLeft(): int {
		$o = $this->getOrientation();
		$this->logger->debug('Image->heightTopLeft() Orientation: ' . $o, ['app' => 'core']);
		switch ($o) {
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

	/**
	 * Outputs the image.
	 *
	 * @param string $mimeType
	 * @return bool
	 */
	#[\Override]
	public function show(?string $mimeType = null): bool {
		if ($mimeType === null) {
			$mimeType = $this->mimeType();
		}
		if ($mimeType !== null) {
			header('Content-Type: ' . $mimeType);
		}
		return $this->_output(null, $mimeType);
	}

	/**
	 * Saves the image.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 */

	#[\Override]
	public function save(?string $filePath = null, ?string $mimeType = null): bool {
		if ($mimeType === null) {
			$mimeType = $this->mimeType();
		}
		if ($filePath === null) {
			if ($this->filePath === null) {
				$this->logger->error(__METHOD__ . '(): called with no path.', ['app' => 'core']);
				return false;
			} else {
				$filePath = $this->filePath;
			}
		}
		return $this->_output($filePath, $mimeType);
	}

	/**
	 * Outputs/saves the image.
	 *
	 * @throws \Exception
	 */
	private function _output(?string $filePath = null, ?string $mimeType = null): bool {
		if ($filePath !== null && $filePath !== '') {
			if (!file_exists(dirname($filePath))) {
				mkdir(dirname($filePath), 0777, true);
			}
			$isWritable = is_writable(dirname($filePath));
			if (!$isWritable) {
				$this->logger->error(__METHOD__ . '(): Directory \'' . dirname($filePath) . '\' is not writable.', ['app' => 'core']);
				return false;
			} elseif (file_exists($filePath) && !is_writable($filePath)) {
				$this->logger->error(__METHOD__ . '(): File \'' . $filePath . '\' is not writable.', ['app' => 'core']);
				return false;
			}
		}
		if (!$this->valid()) {
			return false;
		}

		$imageType = $this->imageType;
		if ($mimeType !== null) {
			switch ($mimeType) {
				case 'image/gif':
					$imageType = IMAGETYPE_GIF;
					break;
				case 'image/jpeg':
					$imageType = IMAGETYPE_JPEG;
					break;
				case 'image/png':
					$imageType = IMAGETYPE_PNG;
					break;
				case 'image/x-xbitmap':
					$imageType = IMAGETYPE_XBM;
					break;
				case 'image/bmp':
				case 'image/x-ms-bmp':
					$imageType = IMAGETYPE_BMP;
					break;
				case 'image/webp':
					$imageType = IMAGETYPE_WEBP;
					break;
				default:
					throw new \Exception('Image::_output(): "' . $mimeType . '" is not supported when forcing a specific output format');
			}
		}

		switch ($imageType) {
			case IMAGETYPE_GIF:
				$retVal = imagegif($this->resource, $filePath);
				break;
			case IMAGETYPE_JPEG:
				if ($this->iccProfile !== null) {
					$retVal = $this->outputWithIccProfile(IMAGETYPE_JPEG, $filePath);
				} else {
					imageinterlace($this->resource, true);
					$retVal = imagejpeg($this->resource, $filePath, $this->getJpegQuality());
				}
				break;
			case IMAGETYPE_PNG:
				if ($this->iccProfile !== null) {
					$retVal = $this->outputWithIccProfile(IMAGETYPE_PNG, $filePath);
				} else {
					$retVal = imagepng($this->resource, $filePath);
				}
				break;
			case IMAGETYPE_XBM:
				if (function_exists('imagexbm')) {
					$retVal = imagexbm($this->resource, $filePath);
				} else {
					throw new \Exception('Image::_output(): imagexbm() is not supported.');
				}

				break;
			case IMAGETYPE_WBMP:
				$retVal = imagewbmp($this->resource, $filePath);
				break;
			case IMAGETYPE_BMP:
				$retVal = imagebmp($this->resource, $filePath);
				break;
			case IMAGETYPE_WEBP:
				$retVal = imagewebp($this->resource, null, $this->getWebpQuality());
				break;
			default:
				$retVal = imagepng($this->resource, $filePath);
		}
		return $retVal;
	}

	/**
	 * Prints the image when called as $image().
	 */
	public function __invoke() {
		return $this->show();
	}

	/**
	 * @param \GdImage $resource
	 */
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

	/**
	 * @return string Returns the mimetype of the data. Returns null if the data is not valid.
	 */
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

	/**
	 * @return null|string Returns the raw image data.
	 */
	#[\Override]
	public function data(): ?string {
		if (!$this->valid()) {
			return null;
		}
		ob_start();
		switch ($this->mimeType) {
			case 'image/png':
				$res = imagepng($this->resource);
				break;
			case 'image/jpeg':
				imageinterlace($this->resource, true);
				$quality = $this->getJpegQuality();
				$res = imagejpeg($this->resource, null, $quality);
				break;
			case 'image/gif':
				$res = imagegif($this->resource);
				break;
			case 'image/webp':
				$res = imagewebp($this->resource, null, $this->getWebpQuality());
				break;
			default:
				$res = imagepng($this->resource);
				$this->logger->info('Image->data. Could not guess mime-type, defaulting to png', ['app' => 'core']);
				break;
		}
		if (!$res) {
			$this->logger->error('Image->data. Error getting image data.', ['app' => 'core']);
		}
		$data = ob_get_clean();
		if ($data === false) {
			return null;
		}
		return $this->embedIccProfile($data);
	}

	/**
	 * Re-embeds the ICC profile into a freshly encoded image, then writes it to
	 * $filePath or outputs it directly when no path is given.
	 */
	private function outputWithIccProfile(int $imageType, ?string $filePath): bool {
		ob_start();
		if ($imageType === IMAGETYPE_PNG) {
			$res = imagepng($this->resource);
		} else {
			imageinterlace($this->resource, true);
			$res = imagejpeg($this->resource, null, $this->getJpegQuality());
		}
		$data = ob_get_clean();
		if (!$res || $data === false) {
			return false;
		}
		$data = $this->embedIccProfile($data);
		if ($filePath === null || $filePath === '') {
			echo $data;
			return true;
		}
		return file_put_contents($filePath, $data) !== false;
	}

	/**
	 * Remembers the source ICC profile for re-embedding into generated output.
	 *
	 * Only RGB profiles are kept: GD converts CMYK and grayscale sources to RGB
	 * pixel data on load, so their source profiles no longer describe the image.
	 */
	private function rememberIccProfile(string $data): void {
		$this->iccProfile = null;
		if (str_starts_with($data, "\xFF\xD8")) {
			$profile = self::extractIccProfileFromJpeg($data);
		} elseif (str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
			$profile = self::extractIccProfileFromPng($data);
		} else {
			return;
		}
		if ($profile !== null && self::isUsableRgbProfile($profile)) {
			$this->iccProfile = $profile;
		}
	}

	private static function isUsableRgbProfile(string $profile): bool {
		return strlen($profile) >= 132 // ICC header plus tag count
			&& substr($profile, 36, 4) === 'acsp' // ICC profile signature
			&& substr($profile, 16, 4) === 'RGB '; // data colour space
	}

	private static function extractIccProfileFromJpeg(string $data): ?string {
		$len = strlen($data);
		$identifierLength = strlen(self::JPEG_ICC_IDENTIFIER);
		$pos = 2;
		$chunks = [];
		$chunkCount = null;
		while ($pos + 4 <= $len) {
			if ($data[$pos] !== "\xFF") {
				return null;
			}
			$marker = ord($data[$pos + 1]);
			if ($marker === 0xFF) {
				// fill byte before a marker
				$pos++;
				continue;
			}
			if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD8)) {
				// standalone marker without a length field
				$pos += 2;
				continue;
			}
			if ($marker === 0xDA || $marker === 0xD9) {
				// start of scan or end of image: no more metadata segments
				break;
			}
			$segmentLength = (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
			if ($segmentLength < 2 || $pos + 2 + $segmentLength > $len) {
				return null;
			}
			if ($marker === 0xE2 && $segmentLength >= 2 + $identifierLength + 2) {
				$payload = substr($data, $pos + 4, $segmentLength - 2);
				if (str_starts_with($payload, self::JPEG_ICC_IDENTIFIER)) {
					$sequence = ord($payload[$identifierLength]);
					$total = ord($payload[$identifierLength + 1]);
					if ($total === 0 || ($chunkCount !== null && $total !== $chunkCount)) {
						return null;
					}
					$chunkCount = $total;
					$chunks[$sequence] = substr($payload, $identifierLength + 2);
				}
			}
			$pos += 2 + $segmentLength;
		}
		if ($chunkCount === null || count($chunks) !== $chunkCount) {
			return null;
		}
		ksort($chunks);
		return implode('', $chunks);
	}

	private static function extractIccProfileFromPng(string $data): ?string {
		$len = strlen($data);
		$pos = 8;
		while ($pos + 8 <= $len) {
			$header = unpack('NchunkLength', $data, $pos);
			if ($header === false) {
				return null;
			}
			$chunkLength = $header['chunkLength'];
			$type = substr($data, $pos + 4, 4);
			if ($type === 'IDAT' || $type === 'IEND') {
				break;
			}
			if ($type === 'iCCP') {
				if ($pos + 8 + $chunkLength > $len) {
					return null;
				}
				$chunk = substr($data, $pos + 8, $chunkLength);
				$separator = strpos($chunk, "\x00");
				if ($separator === false || $separator < 1 || $separator > 79 || strlen($chunk) < $separator + 2) {
					return null;
				}
				if (ord($chunk[$separator + 1]) !== 0) {
					// unknown compression method
					return null;
				}
				$profile = @gzuncompress(substr($chunk, $separator + 2));
				return $profile === false ? null : $profile;
			}
			$pos += 12 + $chunkLength;
		}
		return null;
	}

	private function embedIccProfile(string $data): string {
		if ($this->iccProfile === null) {
			return $data;
		}
		if (str_starts_with($data, "\xFF\xD8")) {
			return $this->embedIccProfileInJpeg($data);
		}
		if (str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
			return $this->embedIccProfileInPng($data);
		}
		return $data;
	}

	private function embedIccProfileInJpeg(string $data): string {
		$chunks = str_split($this->iccProfile, self::JPEG_ICC_MAX_CHUNK_SIZE);
		$total = count($chunks);
		if ($total > 255) {
			return $data;
		}
		// APP2 segments belong before the image data, after the APP0/APP1
		// (JFIF/EXIF) segments the encoder may have written
		$len = strlen($data);
		$pos = 2;
		while ($pos + 4 <= $len
			&& $data[$pos] === "\xFF"
			&& (ord($data[$pos + 1]) === 0xE0 || ord($data[$pos + 1]) === 0xE1)) {
			$segmentLength = (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
			if ($segmentLength < 2 || $pos + 2 + $segmentLength > $len) {
				return $data;
			}
			$pos += 2 + $segmentLength;
		}
		$segments = '';
		foreach ($chunks as $index => $chunk) {
			$payload = self::JPEG_ICC_IDENTIFIER . chr($index + 1) . chr($total) . $chunk;
			$segments .= "\xFF\xE2" . pack('n', strlen($payload) + 2) . $payload;
		}
		return substr($data, 0, $pos) . $segments . substr($data, $pos);
	}

	private function embedIccProfileInPng(string $data): string {
		// IHDR is required to be first and has a fixed size; iCCP belongs before PLTE and IDAT
		$ihdrEnd = 8 + 8 + 13 + 4;
		if (strlen($data) < $ihdrEnd || substr($data, 12, 4) !== 'IHDR') {
			return $data;
		}
		$chunkData = "ICC profile\x00\x00" . gzcompress($this->iccProfile);
		$payload = 'iCCP' . $chunkData;
		$chunk = pack('N', strlen($chunkData)) . $payload . pack('N', crc32($payload));
		return substr($data, 0, $ihdrEnd) . $chunk . substr($data, $ihdrEnd);
	}

	/**
	 * @return string - base64 encoded, which is suitable for embedding in a VCard.
	 */
	public function __toString(): string {
		$data = $this->data();
		if ($data === null) {
			return '';
		} else {
			return base64_encode($data);
		}
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
		if (!isset($exif['Orientation'])) {
			return false;
		}

		if (!is_numeric($exif['Orientation'])) {
			return false;
		}

		return true;
	}

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Get the orientation based on EXIF data.
	 *
	 * @return int The orientation or -1 if no EXIF data is available.
	 */
	#[\Override]
	public function getOrientation(): int {
		if ($this->exif !== null) {
			return $this->exif['Orientation'];
		}

		if ($this->imageType !== IMAGETYPE_JPEG) {
			$this->logger->debug('Image->fixOrientation() Image is not a JPEG.', ['app' => 'core']);
			return -1;
		}
		if (!is_callable('exif_read_data')) {
			$this->logger->debug('Image->fixOrientation() Exif module not enabled.', ['app' => 'core']);
			return -1;
		}
		if (!$this->valid()) {
			$this->logger->debug('Image->fixOrientation() No image loaded.', ['app' => 'core']);
			return -1;
		}
		if (is_null($this->filePath) || !is_readable($this->filePath)) {
			$this->logger->debug('Image->fixOrientation() No readable file path set.', ['app' => 'core']);
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
			$this->logger->debug('Image->fixOrientation() Exif module not enabled.', ['app' => 'core']);
			return;
		}
		if (!$this->valid()) {
			$this->logger->debug('Image->fixOrientation() No image loaded.', ['app' => 'core']);
			return;
		}

		$exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($data));
		if ($exif === false || !$this->isValidExifData($exif)) {
			return;
		}
		$this->exif = $exif;
	}

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Fixes orientation based on EXIF data.
	 *
	 * @return bool
	 */
	#[\Override]
	public function fixOrientation(): bool {
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}
		$o = $this->getOrientation();
		$this->logger->debug('Image->fixOrientation() Orientation: ' . $o, ['app' => 'core']);
		$rotate = 0;
		$flip = false;
		switch ($o) {
			case -1:
				return false; //Nothing to fix
			case 1:
				$rotate = 0;
				break;
			case 2:
				$rotate = 0;
				$flip = true;
				break;
			case 3:
				$rotate = 180;
				break;
			case 4:
				$rotate = 180;
				$flip = true;
				break;
			case 5:
				$rotate = 90;
				$flip = true;
				break;
			case 6:
				$rotate = 270;
				break;
			case 7:
				$rotate = 270;
				$flip = true;
				break;
			case 8:
				$rotate = 90;
				break;
		}
		if ($flip && function_exists('imageflip')) {
			imageflip($this->resource, IMG_FLIP_HORIZONTAL);
		}
		if ($rotate) {
			$res = imagerotate($this->resource, $rotate, 0);
			if ($res) {
				if (imagealphablending($res, true)) {
					if (imagesavealpha($res, true)) {
						$this->resource = $res;
						return true;
					} else {
						$this->logger->debug('Image->fixOrientation() Error during alpha-saving', ['app' => 'core']);
						return false;
					}
				} else {
					$this->logger->debug('Image->fixOrientation() Error during alpha-blending', ['app' => 'core']);
					return false;
				}
			} else {
				$this->logger->debug('Image->fixOrientation() Error during orientation fixing', ['app' => 'core']);
				return false;
			}
		}
		return false;
	}

	/**
	 * Loads an image from an open file handle.
	 * It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	 *
	 * @param resource $handle
	 * @return \GdImage|false An image resource or false on error
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
	 *
	 * @param int $width The image width.
	 * @param int $height The image height.
	 * @return bool true if allocating is allowed, false otherwise
	 */
	private function checkImageMemory($width, $height) {
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
	 *
	 * @param string $path The path to a local file.
	 * @return bool true if allocating is allowed, false otherwise
	 */
	private function checkImageSize($path) {
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
	 * Check if loading an image from the given data is allowed.
	 *
	 * @param string $data A string of image data as read from a file.
	 * @return bool true if allocating is allowed, false otherwise
	 */
	private function checkImageDataSize($data) {
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
	 * Loads an image from a local file.
	 *
	 * @param bool|string $imagePath The path to a local file.
	 * @return bool|\GdImage An image resource or false on error
	 */
	public function loadFromFile($imagePath = false) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if (is_bool($imagePath) || !@is_file($imagePath) || !file_exists($imagePath) || filesize($imagePath) < 12 || !is_readable($imagePath)) {
			return false;
		}
		$iType = exif_imagetype($imagePath);
		switch ($iType) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					$this->resource = imagecreatefromgif($imagePath);
					if ($this->resource) {
						// Preserve transparency
						imagealphablending($this->resource, true);
						imagesavealpha($this->resource, true);
					} else {
						$this->logger->debug('Image->loadFromFile, GIF image not valid: ' . $imagePath, ['app' => 'core']);
					}
				} else {
					$this->logger->debug('Image->loadFromFile, GIF images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					if (@getimagesize($imagePath) !== false) {
						$this->resource = @imagecreatefromjpeg($imagePath);
					} else {
						$this->logger->debug('Image->loadFromFile, JPG image not valid: ' . $imagePath, ['app' => 'core']);
					}
				} else {
					$this->logger->debug('Image->loadFromFile, JPG images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					$this->resource = @imagecreatefrompng($imagePath);
					if ($this->resource) {
						// Preserve transparency
						imagealphablending($this->resource, true);
						imagesavealpha($this->resource, true);
					} else {
						$this->logger->debug('Image->loadFromFile, PNG image not valid: ' . $imagePath, ['app' => 'core']);
					}
				} else {
					$this->logger->debug('Image->loadFromFile, PNG images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_XBM:
				if (imagetypes() & IMG_XPM) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					$this->resource = @imagecreatefromxbm($imagePath);
				} else {
					$this->logger->debug('Image->loadFromFile, XBM/XPM images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_WBMP:
				if (imagetypes() & IMG_WBMP) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					$this->resource = @imagecreatefromwbmp($imagePath);
				} else {
					$this->logger->debug('Image->loadFromFile, WBMP images not supported: ' . $imagePath, ['app' => 'core']);
				}
				break;
			case IMAGETYPE_BMP:
				$this->resource = imagecreatefrombmp($imagePath);
				break;
			case IMAGETYPE_WEBP:
				if (imagetypes() & IMG_WEBP) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}

					// Check for animated header before generating preview since libgd does not handle them well
					// Adapted from here: https://stackoverflow.com/a/68491679/4085517 (stripped to only to check for animations + added additional error checking)
					// Header format details here: https://developers.google.com/speed/webp/docs/riff_container

					// Load up the header data, if any
					$fp = fopen($imagePath, 'rb');
					if (!$fp) {
						return false;
					}
					$data = fread($fp, 90);
					if ($data === false) {
						return false;
					}
					fclose($fp);
					unset($fp);

					$headerFormat = 'A4Riff/' // get n string
						. 'I1Filesize/' // get integer (file size but not actual size)
						. 'A4Webp/' // get n string
						. 'A4Vp/' // get n string
						. 'A74Chunk';

					$header = unpack($headerFormat, $data);
					unset($data, $headerFormat);
					if ($header === false) {
						return false;
					}

					// Check if we're really dealing with a valid WEBP header rather than just one suffixed ".webp"
					if (!isset($header['Riff']) || strtoupper($header['Riff']) !== 'RIFF') {
						return false;
					}
					if (!isset($header['Webp']) || strtoupper($header['Webp']) !== 'WEBP') {
						return false;
					}
					if (!isset($header['Vp']) || strpos(strtoupper($header['Vp']), 'VP8') === false) {
						return false;
					}

					// Check for animation indicators
					if (strpos(strtoupper($header['Chunk']), 'ANIM') !== false || strpos(strtoupper($header['Chunk']), 'ANMF') !== false) {
						// Animated so don't let it reach libgd
						$this->logger->debug('Image->loadFromFile, animated WEBP images not supported: ' . $imagePath, ['app' => 'core']);
					} else {
						// We're safe so give it to libgd
						$this->resource = @imagecreatefromwebp($imagePath);
					}
				} else {
					$this->logger->debug('Image->loadFromFile, WEBP images not supported: ' . $imagePath, ['app' => 'core']);
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
				$iType = IMAGETYPE_PNG;
				$this->logger->debug('Image->loadFromFile, Default', ['app' => 'core']);
				break;
		}
		if ($this->valid()) {
			$this->imageType = $iType;
			$this->mimeType = image_type_to_mime_type($iType);
			$this->filePath = $imagePath;
			if ($iType === IMAGETYPE_JPEG || $iType === IMAGETYPE_PNG) {
				$header = @file_get_contents($imagePath, false, null, 0, self::ICC_SCAN_BYTE_LIMIT);
				if ($header !== false) {
					$this->rememberIccProfile($header);
				}
			}
		}
		return $this->resource;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function loadFromData(string $str): GdImage|false {
		if (!$this->checkImageDataSize($str)) {
			return false;
		}
		$this->resource = @imagecreatefromstring($str);
		if ($this->fileInfo) {
			$this->mimeType = $this->fileInfo->buffer($str);
		}
		if ($this->valid()) {
			imagealphablending($this->resource, false);
			imagesavealpha($this->resource, true);
			$this->rememberIccProfile($str);
		}

		if (!$this->resource) {
			$this->logger->debug('Image->loadFromFile, could not load', ['app' => 'core']);
			return false;
		}
		return $this->resource;
	}

	/**
	 * Loads an image from a base64 encoded string.
	 *
	 * @param string $str A string base64 encoded string of image data.
	 * @return bool|\GdImage An image resource or false on error
	 */
	public function loadFromBase64(string $str) {
		$data = base64_decode($str);
		if ($data) { // try to load from string data
			if (!$this->checkImageDataSize($data)) {
				return false;
			}
			$this->resource = @imagecreatefromstring($data);
			if ($this->fileInfo) {
				$this->mimeType = $this->fileInfo->buffer($data);
			}
			if (!$this->resource) {
				$this->logger->debug('Image->loadFromBase64, could not load', ['app' => 'core']);
				return false;
			}
			$this->rememberIccProfile($data);
			return $this->resource;
		} else {
			return false;
		}
	}

	/**
	 * Resizes the image preserving ratio.
	 *
	 * @param int $maxSize The maximum size of either the width or height.
	 * @return bool
	 */
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

	/**
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
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

	/**
	 * Crops the image to the middle square. If the image is already square it just returns.
	 *
	 * @param int $size maximum size for the result (optional)
	 * @return bool for success or failure
	 */
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

	/**
	 * Crops the image from point $x$y with dimension $wx$h.
	 *
	 * @param int $x Horizontal position
	 * @param int $y Vertical position
	 * @param int $w Width
	 * @param int $h Height
	 * @return bool for success or failure
	 */
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
	 * Crops the image from point $x$y with dimension $wx$h.
	 *
	 * @param int $x Horizontal position
	 * @param int $y Vertical position
	 * @param int $w Width
	 * @param int $h Height
	 * @return \GdImage|false
	 */
	public function cropNew(int $x, int $y, int $w, int $h) {
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

	/**
	 * Resizes the image to fit within a boundary while preserving ratio.
	 *
	 * Warning: Images smaller than $maxWidth x $maxHeight will end up being scaled up
	 *
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return bool
	 */
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

	/**
	 * Shrinks larger images to fit within specified boundaries while preserving ratio.
	 *
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @return bool
	 */
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
		$image->iccProfile = $this->iccProfile;

		return $image;
	}

	#[\Override]
	public function cropCopy(int $x, int $y, int $w, int $h): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->iccProfile = $this->iccProfile;
		$image->resource = $this->cropNew($x, $y, $w, $h);

		return $image;
	}

	#[\Override]
	public function preciseResizeCopy(int $width, int $height): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->iccProfile = $this->iccProfile;
		$image->resource = $this->preciseResizeNew($width, $height);

		return $image;
	}

	#[\Override]
	public function resizeCopy(int $maxSize): IImage {
		$image = new self($this->logger, $this->appConfig, $this->config);
		$image->imageType = $this->imageType;
		$image->mimeType = $this->mimeType;
		$image->iccProfile = $this->iccProfile;
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
	function exif_imagetype(string $fileName) {
		if (($info = getimagesize($fileName)) !== false) {
			return $info[2];
		}
		return false;
	}
}
