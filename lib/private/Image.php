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

	// Upper bound for carried ICC profiles, display profiles are a few KiB at most
	private const ICC_MAX_PROFILE_SIZE = 64 * 1024;

	// D50 adapted sRGB primaries as stored in the colorant tags of ICC profiles
	private const SRGB_COLORANTS = [
		'rXYZ' => [0.4361, 0.2225, 0.0139],
		'gXYZ' => [0.3851, 0.7169, 0.0971],
		'bXYZ' => [0.1431, 0.0606, 0.7141],
	];

	// Output of the sRGB tone curve for an input of 0.5
	private const SRGB_TRC_MIDPOINT = 0.2140;

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
				case 'image/avif':
					$imageType = IMAGETYPE_AVIF;
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
			case IMAGETYPE_AVIF:
				$retVal = imageavif($this->resource, $filePath);
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
		return self::embedIccProfile($data, $this->iccProfile);
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
		$data = self::embedIccProfile($data, $this->iccProfile);
		if ($filePath === null || $filePath === '') {
			echo $data;
			return true;
		}
		return file_put_contents($filePath, $data) !== false;
	}

	/**
	 * Returns the ICC profile of an encoded JPEG or PNG image that has to be
	 * carried into images derived from it, or null if there is none.
	 *
	 * Only RGB profiles are kept: GD converts CMYK and grayscale sources to RGB
	 * pixel data on load, so their source profiles no longer describe the image.
	 */
	public static function getIccProfile(string $data): ?string {
		return self::readIccProfile(self::stringReader($data));
	}

	/**
	 * Embeds the ICC profile into an encoded JPEG or PNG image without a profile.
	 * Other image formats are returned unchanged.
	 */
	public static function embedIccProfile(string $data, ?string $profile): string {
		if ($profile === null) {
			return $data;
		}
		if (str_starts_with($data, "\xFF\xD8")) {
			return self::embedIccProfileInJpeg($data, $profile);
		}
		if (str_starts_with($data, "\x89PNG\r\n\x1a\n")) {
			return self::embedIccProfileInPng($data, $profile);
		}
		return $data;
	}

	/**
	 * @param \Closure(int, int): string $read
	 */
	private static function readIccProfile(\Closure $read): ?string {
		$signature = $read(0, 8);
		if (str_starts_with($signature, "\xFF\xD8")) {
			$profile = self::extractIccProfileFromJpeg($read);
		} elseif ($signature === "\x89PNG\r\n\x1a\n") {
			$profile = self::extractIccProfileFromPng($read);
		} else {
			return null;
		}
		if ($profile === null || !self::isUsableRgbProfile($profile) || self::isSrgbProfile($profile)) {
			return null;
		}
		return $profile;
	}

	/**
	 * @return \Closure(int, int): string reads up to $length bytes at $offset of $data
	 */
	private static function stringReader(string $data): \Closure {
		return static fn (int $offset, int $length): string => substr($data, $offset, $length);
	}

	/**
	 * @param resource $handle seekable stream
	 * @return \Closure(int, int): string reads up to $length bytes at $offset of the stream
	 */
	private static function streamReader($handle): \Closure {
		return static function (int $offset, int $length) use ($handle): string {
			if ($length < 1 || fseek($handle, $offset) !== 0) {
				return '';
			}
			return (string)fread($handle, $length);
		};
	}

	/**
	 * Whether the profile describes sRGB, which is what untagged images are
	 * displayed as anyway, so embedding it would only grow the output.
	 */
	private static function isSrgbProfile(string $profile): bool {
		$tags = self::iccTags($profile);
		foreach (self::SRGB_COLORANTS as $signature => $expected) {
			if (!isset($tags[$signature]) || substr($profile, $tags[$signature], 4) !== 'XYZ ') {
				return false;
			}
			foreach ($expected as $index => $value) {
				$actual = self::iccS15Fixed16($profile, $tags[$signature] + 8 + 4 * $index);
				if ($actual === null || abs($actual - $value) > 0.002) {
					return false;
				}
			}
		}
		foreach (['rTRC', 'gTRC', 'bTRC'] as $signature) {
			$midpoint = isset($tags[$signature]) ? self::iccCurveMidpoint($profile, $tags[$signature]) : null;
			if ($midpoint === null || abs($midpoint - self::SRGB_TRC_MIDPOINT) > 0.01) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return array<string, int> offsets of the tag data by tag signature
	 */
	private static function iccTags(string $profile): array {
		$length = strlen($profile);
		$count = unpack('N', $profile, 128)[1];
		if (132 + 12 * $count > $length) {
			return [];
		}
		$tags = [];
		for ($i = 0; $i < $count; $i++) {
			['offset' => $offset, 'size' => $size] = unpack('Noffset/Nsize', $profile, 132 + 12 * $i + 4);
			if ($offset + $size <= $length) {
				$tags[substr($profile, 132 + 12 * $i, 4)] = $offset;
			}
		}
		return $tags;
	}

	private static function iccS15Fixed16(string $profile, int $offset): ?float {
		if ($offset + 4 > strlen($profile)) {
			return null;
		}
		$value = unpack('N', $profile, $offset)[1];
		return ($value >= 0x80000000 ? $value - 0x100000000 : $value) / 65536;
	}

	/**
	 * Evaluates a 'curv' or 'para' tone curve tag at an input of 0.5.
	 */
	private static function iccCurveMidpoint(string $profile, int $offset): ?float {
		$type = substr($profile, $offset, 4);
		if ($type === 'curv') {
			$count = unpack('N', $profile, $offset + 8)[1];
			if ($count === 0) {
				return 0.5;
			}
			if ($offset + 12 + 2 * $count > strlen($profile)) {
				return null;
			}
			if ($count === 1) {
				return 0.5 ** (unpack('n', $profile, $offset + 12)[1] / 256);
			}
			$position = ($count - 1) / 2;
			$low = unpack('n', $profile, $offset + 12 + 2 * (int)floor($position))[1];
			$high = unpack('n', $profile, $offset + 12 + 2 * (int)ceil($position))[1];
			return ($low + $high) / 2 / 65535;
		}
		if ($type === 'para') {
			$function = unpack('n', $profile, $offset + 8)[1];
			$parameterCount = [1, 3, 4, 5, 7][$function] ?? null;
			if ($parameterCount === null) {
				return null;
			}
			$parameters = [];
			for ($i = 0; $i < $parameterCount; $i++) {
				$parameters[] = self::iccS15Fixed16($profile, $offset + 12 + 4 * $i);
			}
			if (in_array(null, $parameters, true)) {
				return null;
			}
			[$g, $a, $b, $c, $d, $e, $f] = array_pad($parameters, 7, 0.0);
			$x = 0.5;
			return match ($function) {
				0 => $x ** $g,
				1 => ($a * $x + $b >= 0) ? ($a * $x + $b) ** $g : 0.0,
				2 => ($a * $x + $b >= 0) ? ($a * $x + $b) ** $g + $c : $c,
				3 => $x >= $d ? ($a * $x + $b) ** $g : $c * $x,
				4 => $x >= $d ? ($a * $x + $b) ** $g + $e : $c * $x + $f,
				default => null,
			};
		}
		return null;
	}

	private static function isUsableRgbProfile(string $profile): bool {
		return strlen($profile) >= 132 // ICC header plus tag count
			&& strlen($profile) <= self::ICC_MAX_PROFILE_SIZE
			&& unpack('N', $profile)[1] === strlen($profile) // declared profile size
			&& substr($profile, 36, 4) === 'acsp' // ICC profile signature
			&& substr($profile, 16, 4) === 'RGB '; // data colour space
	}

	/**
	 * @param \Closure(int, int): string $read
	 */
	private static function extractIccProfileFromJpeg(\Closure $read): ?string {
		$identifierLength = strlen(self::JPEG_ICC_IDENTIFIER);
		$pos = 2;
		$chunks = [];
		$chunkCount = null;
		$profileSize = 0;
		while (strlen($header = $read($pos, 4)) === 4) {
			if ($header[0] !== "\xFF") {
				return null;
			}
			$marker = ord($header[1]);
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
			$segmentLength = unpack('n', $header, 2)[1];
			if ($segmentLength < 2) {
				return null;
			}
			if ($marker === 0xE2 && $segmentLength >= 2 + $identifierLength + 2) {
				$payload = $read($pos + 4, $segmentLength - 2);
				if (strlen($payload) !== $segmentLength - 2) {
					return null;
				}
				if (str_starts_with($payload, self::JPEG_ICC_IDENTIFIER)) {
					$sequence = ord($payload[$identifierLength]);
					$total = ord($payload[$identifierLength + 1]);
					if ($total === 0 || ($chunkCount !== null && $total !== $chunkCount)) {
						return null;
					}
					$chunkCount = $total;
					$chunks[$sequence] = substr($payload, $identifierLength + 2);
					$profileSize += strlen($chunks[$sequence]);
					if ($profileSize > self::ICC_MAX_PROFILE_SIZE) {
						return null;
					}
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

	/**
	 * @param \Closure(int, int): string $read
	 */
	private static function extractIccProfileFromPng(\Closure $read): ?string {
		$pos = 8;
		while (strlen($header = $read($pos, 8)) === 8) {
			$chunkLength = unpack('N', $header)[1];
			$type = substr($header, 4, 4);
			if ($type === 'IDAT' || $type === 'IEND') {
				break;
			}
			if ($type === 'iCCP') {
				if ($chunkLength > self::ICC_MAX_PROFILE_SIZE) {
					return null;
				}
				$chunk = $read($pos + 8, $chunkLength);
				if (strlen($chunk) !== $chunkLength) {
					return null;
				}
				$separator = strpos($chunk, "\x00");
				if ($separator === false || $separator < 1 || $separator > 79 || strlen($chunk) < $separator + 2) {
					return null;
				}
				if (ord($chunk[$separator + 1]) !== 0) {
					// unknown compression method
					return null;
				}
				$profile = @gzuncompress(substr($chunk, $separator + 2), self::ICC_MAX_PROFILE_SIZE);
				return $profile === false ? null : $profile;
			}
			$pos += 12 + $chunkLength;
		}
		return null;
	}

	private static function embedIccProfileInJpeg(string $data, string $profile): string {
		$chunks = str_split($profile, self::JPEG_ICC_MAX_CHUNK_SIZE);
		$total = count($chunks);
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

	private static function embedIccProfileInPng(string $data, string $profile): string {
		// IHDR is required to be first and has a fixed size; iCCP belongs before PLTE and IDAT
		$ihdrEnd = 8 + 8 + 13 + 4;
		if (strlen($data) < $ihdrEnd || substr($data, 12, 4) !== 'IHDR') {
			return $data;
		}
		$chunkData = "ICC profile\x00\x00" . gzcompress($profile);
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
			case IMAGETYPE_AVIF:
				if (imagetypes() & IMG_AVIF) {
					if (!$this->checkImageSize($imagePath)) {
						return false;
					}
					// An animated AVIF decodes to its first frame, which is the
					// right thing for a preview, and a sequence libgd cannot read
					// returns false and is handled below like any other failure
					$this->resource = @imagecreatefromavif($imagePath);
				} else {
					$this->logger->debug('Image->loadFromFile, AVIF images not supported: ' . $imagePath, ['app' => 'core']);
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
			$this->iccProfile = null;
			if ($iType === IMAGETYPE_JPEG || $iType === IMAGETYPE_PNG) {
				$handle = @fopen($imagePath, 'rb');
				if ($handle !== false) {
					$this->iccProfile = self::readIccProfile(self::streamReader($handle));
					fclose($handle);
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
			$this->iccProfile = self::getIccProfile($str);
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
			$this->iccProfile = self::getIccProfile($data);
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
		if ($widthOrig === $heightOrig && $size === 0) {
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
