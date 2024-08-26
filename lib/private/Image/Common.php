<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bartek Przybylski <bart.p.pl@gmail.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Byron Marohn <combustible@live.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author j-ed <juergen@eisfair.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Johannes Willnecker <johannes@willnecker.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Samuel CHEMLA <chemla.samuel@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Image;

use OCP\IImage;

/**
 * Class for basic image manipulation
 */
abstract class Common implements IImage {
	/** @var bool|object|resource */
	protected $resource = false; // tmp resource.

	/** @var int */
	protected $imageType = IMAGETYPE_PNG; // Default to png if file type isn't evident.

	/** @var null|string */
	protected $mimeType = 'image/png'; // Default to png

	/** @var null|string */
	protected $filePath = null;

	/** @var \finfo */
	protected $fileInfo;

	/** @var \OCP\ILogger */
	protected $logger;

	/** @var \OCP\IConfig */
	protected $config;

	/** @var ?array [header => value] */
	protected $exif;

	/**
	 * @inheritDoc
	 */
	public function __construct(\OCP\ILogger $logger = null, \OCP\IConfig $config = null) {
		$this->logger = $logger;
		if ($logger === null) {
			$this->logger = \OC::$server->getLogger();
		}

		$this->config = $config;
		if ($config === null) {
			$this->config = \OC::$server->getConfig();
		}

		if (\OC_Util::fileInfoLoaded()) {
			$this->fileInfo = new \finfo(FILEINFO_MIME_TYPE);
		}
	}

	/**
	 * @inheritDoc
	 */
	abstract public function valid(): bool;

	/**
	 * @inheritDoc
	 */
	public function mimeType(): ?string {
		return $this->valid() ? $this->mimeType : null;
	}

	/**
	 * @inheritDoc
	 */
	abstract public function width(): int;

	/**
	 * @inheritDoc
	 */
	abstract public function height(): int;

	/**
	 * @inheritDoc
	 */
	public function widthTopLeft(): int {
		$o = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $o, ['app' => 'core']);
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
	 * @inheritDoc
	 */
	public function heightTopLeft(): int {
		$o = $this->getOrientation();
		$this->logger->debug(__METHOD__ . '() Orientation: ' . $o, ['app' => 'core']);
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
	 * @inheritDoc
	 */
	public function show(?string $mimeType = null): bool {
		if ($mimeType === null) {
			$mimeType = $this->mimeType();
		}
		header('Content-Type: ' . $mimeType);
		return $this->_output(null, $mimeType);
	}

	/**
	 * @inheritDoc
	 */
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
	 * @inheritDoc
	 */
	public function __invoke() {
		return $this->show();
	}

	/**
	 * @param resource Returns the image resource in any.
	 * @throws \InvalidArgumentException in case the supplied resource does not have the type "gd"
	 */
	abstract public function setResource($resource): void;

	/**
	 * @inheritDoc
	 */
	public function resource() {
		return $this->resource;
	}

	/**
	 * @inheritDoc
	 */
	public function dataMimeType(): ?string {
		if (!$this->valid()) {
			return null;
		}

		switch ($this->mimeType) {
			case 'image/png':
			case 'image/jpeg':
			case 'image/gif':
				return $this->mimeType;
			default:
				return 'image/png';
		}
	}

	/**
	 * Get JPEG quality setting.
	 *
	 * @return int between 10 and 100, defaults to 90
	 */
	protected function getJpegQuality(): int {
		$quality = $this->config->getAppValue('preview', 'jpeg_quality', '90');
		assert($quality !== null); // TODO: remove when getAppValue is type safe
		return min(100, max(10, (int) $quality));
	}

	/**
	 * @return string - base64 encoded, which is suitable for embedding in a VCard.
	 */
	public function __toString(): string {
		return base64_encode($this->data());
	}

	public function __destruct() {
		$this->destroy();
	}

	/**
	 * Write/saves the image and handles output file paths.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 * @throws \Exception
	 */
	protected function _output($filePath = null, $mimeType = null): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		if ($filePath) {
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

		return $this->_write($filePath = null, $mimeType = null);
	}

	/**
	 * Write/saves the image.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 * @throws \Exception
	 */
	abstract protected function _write($filePath = null, $mimeType = null): bool;

	/**
	 * @inheritDoc
	 */
	abstract public function data(): ?string;

	/**
	 * @inheritDoc
	 */
	public function getOrientation(): int {
		if ($this->exif !== null) {
			return $this->exif['Orientation'];
		}

		if ($this->imageType !== IMAGETYPE_JPEG) {
			$this->logger->debug(__METHOD__ . '(): Image is not a JPEG.', ['app' => 'core']);
			return -1;
		}
		if (!is_callable('exif_read_data')) {
			$this->logger->debug(__METHOD__ . '(): Exif module not enabled.', ['app' => 'core']);
			return -1;
		}
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded.', ['app' => 'core']);
			return -1;
		}
		if (is_null($this->filePath) || !is_readable($this->filePath)) {
			$this->logger->debug(__METHOD__ . '(): No readable file path set.', ['app' => 'core']);
			return -1;
		}
		$exif = @exif_read_data($this->filePath, 'IFD0');
		if (!$exif) {
			return -1;
		}
		if (!isset($exif['Orientation'])) {
			return -1;
		}
		$this->exif = $exif;
		return $exif['Orientation'];
	}

	/**
	 * Reads the EXIF headers from an image data stream
	 *
	 * @param $data image data
	 */
	public function readExif($data): void {
		if (!is_callable('exif_read_data')) {
			$this->logger->debug(__METHOD__ . '(): Exif module not enabled.', ['app' => 'core']);
			return;
		}
		if (!$this->valid()) {
			$this->logger->debug(__METHOD__ . '(): No image loaded.', ['app' => 'core']);
			return;
		}

		$exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($data));
		if (!$exif) {
			return;
		}
		if (!isset($exif['Orientation'])) {
			return;
		}
		$this->exif = $exif;
	}

	/**
	 * @inheritDoc
	 */
	abstract public function fixOrientation(): bool;

	/**
	 * Loads an image from a local file.
	 *
	 * @param bool|string $imagePath The path to a local file.
	 * @return bool|object|resource An image resource or false on error
	 */
	abstract public function loadFromFile($imagePath = false);

	/**
	 * Loads an image from an open file handle.
	 * It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	 *
	 * @param resource $handle
	 * @return bool|object|resource A raw image resource or false on error
	 */
	abstract public function loadFromFileHandle($handle);

	/**
	 * Loads an image from a string of data.
	 *
	 * @param string $str A string of image data as read from a file.
	 * @return bool|object|resource A raw image resource or false on error
	 */
	abstract public function loadFromData(string $str);

	/**
	 * Loads an image from a base64 encoded string.
	 *
	 * @param string $str A string base64 encoded string of image data.
	 * @return bool|object|resource A raw image resource or false on error
	 */
	public function loadFromBase64(string $str) {
		return $this->loadFromData(base64_decode($str));
	}

	/**
	 * @inheritDoc
	 */
	abstract public function resize(int $maxSize): bool;

	/**
	 * @param $maxSize
	 * @return resource | bool
	 */
	abstract public function resizeNew(int $maxSize);

	/**
	 * @inheritDoc
	 */
	abstract public function preciseResize(int $width, int $height): bool;

	/**
	 * @param int $width
	 * @param int $height
	 * @return resource | bool
	 */
	abstract public function preciseResizeNew(int $width, int $height);

	/**
	 * @inheritDoc
	 */
	abstract public function centerCrop(int $size = 0): bool;

	/**
	 * @inheritDoc
	 */
	abstract public function crop(int $x, int $y, int $w, int $h): bool;

	/**
	 * Crops the image from point $x$y with dimension $wx$h.
	 *
	 * @param int $x Horizontal position
	 * @param int $y Vertical position
	 * @param int $w Width
	 * @param int $h Height
	 * @return resource | bool
	 */
	abstract public function cropNew(int $x, int $y, int $w, int $h);

	/**
	 * @inheritDoc
	 */
	abstract public function fitIn(int $maxWidth, int $maxHeight): bool;

	/**
	 * @inheritDoc
	 */
	public function scaleDownToFit(int $maxWidth, int $maxHeight): bool {
		if (!$this->valid()) {
			$this->logger->error(__METHOD__ . '(): No image loaded', ['app' => 'core']);
			return false;
		}

		$widthOrig = $this->width();
		$heightOrig = $this->height();

		if ($widthOrig > $maxWidth || $heightOrig > $maxHeight) {
			return $this->fitIn($maxWidth, $maxHeight);
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function copy(): IImage {
		return clone $this->resource;
	}

	/**
	 * @inheritDoc
	 */
	abstract public function cropCopy(int $x, int $y, int $w, int $h): IImage;

	/**
	 * @inheritDoc
	 */
	abstract public function preciseResizeCopy(int $width, int $height): IImage;

	/**
	 * @inheritDoc
	 */
	abstract public function resizeCopy(int $maxSize): IImage;

	/**
	 * Destroys the current image and resets the object
	 */
	abstract public function destroy(): void;
}

if (!function_exists('exif_imagetype')) {
	/**
	 * Workaround if exif_imagetype does not exist
	 *
	 * @link https://www.php.net/manual/en/function.exif-imagetype.php#80383
	 * @param string $fileName
	 * @return string|boolean
	 */
	function exif_imagetype(string $fileName) {
		if (($info = getimagesize($fileName)) !== false) {
			return $info[2];
		}
		return false;
	}
}
