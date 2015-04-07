<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <owncloud@interfasys.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OC\Preview\Provider;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;

class Preview {
	//the thumbnail folder
	const THUMBNAILS_FOLDER = 'thumbnails';

	//config
	private $maxScaleFactor;
	private $configMaxX;
	private $configMaxY;

	//fileview object
	private $fileView = null;
	private $userView = null;

	//vars
	private $file;
	private $maxX;
	private $maxY;
	private $scalingUp;
	private $mimeType;
	private $keepAspect = false;

	//filemapper used for deleting previews
	// index is path, value is fileinfo
	static public $deleteFileMapper = array();
	static public $deleteChildrenMapper = array();

	/**
	 * preview images object
	 *
	 * @var \OCP\IImage
	 */
	private $preview;

	/**
	 * @var \OCP\Files\FileInfo
	 */
	protected $info;

	/**
	 * check if thumbnail or bigger version of thumbnail of file is cached
	 * @param string $user userid - if no user is given, OC_User::getUser will be used
	 * @param string $root path of root
	 * @param string $file The path to the file where you want a thumbnail from
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingUp Disable/Enable upscaling of previews
	 * @throws \Exception
	 * @return mixed (bool / string)
	 *                    false if thumbnail does not exist
	 *                    path to thumbnail if thumbnail exists
	 */
	public function __construct($user = '', $root = '/', $file = '', $maxX = 1, $maxY = 1, $scalingUp = true) {
		//init fileviews
		if ($user === '') {
			$user = \OC_User::getUser();
		}
		$this->fileView = new \OC\Files\View('/' . $user . '/' . $root);
		$this->userView = new \OC\Files\View('/' . $user);

		//set config
		$this->configMaxX = \OC::$server->getConfig()->getSystemValue('preview_max_x', 2048);
		$this->configMaxY = \OC::$server->getConfig()->getSystemValue('preview_max_y', 2048);
		$this->maxScaleFactor = \OC::$server->getConfig()->getSystemValue('preview_max_scale_factor', 2);

		//save parameters
		$this->setFile($file);
		$this->setMaxX($maxX);
		$this->setMaxY($maxY);
		$this->setScalingUp($scalingUp);

		$this->preview = null;

		//check if there are preview backends
		if (!\OC::$server->getPreviewManager()->hasProviders() && \OC::$server->getConfig()->getSystemValue('enable_previews', true)) {
			\OC_Log::write('core', 'No preview providers exist', \OC_Log::ERROR);
			throw new \Exception('No preview providers');
		}
	}

	/**
	 * returns the path of the file you want a thumbnail from
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * returns the max width of the preview
	 * @return integer
	 */
	public function getMaxX() {
		return $this->maxX;
	}

	/**
	 * returns the max height of the preview
	 * @return integer
	 */
	public function getMaxY() {
		return $this->maxY;
	}

	/**
	 * returns whether or not scalingup is enabled
	 * @return bool
	 */
	public function getScalingUp() {
		return $this->scalingUp;
	}

	/**
	 * returns the name of the thumbnailfolder
	 * @return string
	 */
	public function getThumbnailsFolder() {
		return self::THUMBNAILS_FOLDER;
	}

	/**
	 * returns the max scale factor
	 * @return string
	 */
	public function getMaxScaleFactor() {
		return $this->maxScaleFactor;
	}

	/**
	 * returns the max width set in ownCloud's config
	 * @return string
	 */
	public function getConfigMaxX() {
		return $this->configMaxX;
	}

	/**
	 * returns the max height set in ownCloud's config
	 * @return string
	 */
	public function getConfigMaxY() {
		return $this->configMaxY;
	}

	/**
	 * @return false|Files\FileInfo|\OCP\Files\FileInfo
	 */
	protected function getFileInfo() {
		$absPath = $this->fileView->getAbsolutePath($this->file);
		$absPath = Files\Filesystem::normalizePath($absPath);
		if(array_key_exists($absPath, self::$deleteFileMapper)) {
			$this->info = self::$deleteFileMapper[$absPath];
		} else if (!$this->info) {
			$this->info = $this->fileView->getFileInfo($this->file);
		}
		return $this->info;
	}


	/**
	 * @return array|null
	 */
	private function getChildren() {
		$absPath = $this->fileView->getAbsolutePath($this->file);
		$absPath = Files\Filesystem::normalizePath($absPath);

		if (array_key_exists($absPath, self::$deleteChildrenMapper)) {
			return self::$deleteChildrenMapper[$absPath];
		}

		return null;
	}

	/**
	 * set the path of the file you want a thumbnail from
	 * @param string $file
	 * @return $this
	 */
	public function setFile($file) {
		$this->file = $file;
		$this->info = null;

		if ($file !== '') {
			$this->getFileInfo();
			if($this->info instanceof \OCP\Files\FileInfo) {
				$this->mimeType = $this->info->getMimetype();
			}
		}
		return $this;
	}

	/**
	 * set mime type explicitly
	 * @param string $mimeType
	 */
	public function setMimetype($mimeType) {
		$this->mimeType = $mimeType;
	}

	/**
	 * set the the max width of the preview
	 * @param int $maxX
	 * @throws \Exception
	 * @return \OC\Preview $this
	 */
	public function setMaxX($maxX = 1) {
		if ($maxX <= 0) {
			throw new \Exception('Cannot set width of 0 or smaller!');
		}
		$configMaxX = $this->getConfigMaxX();
		if (!is_null($configMaxX)) {
			if ($maxX > $configMaxX) {
				\OCP\Util::writeLog('core', 'maxX reduced from ' . $maxX . ' to ' . $configMaxX, \OCP\Util::DEBUG);
				$maxX = $configMaxX;
			}
		}
		$this->maxX = $maxX;
		return $this;
	}

	/**
	 * set the the max height of the preview
	 * @param int $maxY
	 * @throws \Exception
	 * @return \OC\Preview $this
	 */
	public function setMaxY($maxY = 1) {
		if ($maxY <= 0) {
			throw new \Exception('Cannot set height of 0 or smaller!');
		}
		$configMaxY = $this->getConfigMaxY();
		if (!is_null($configMaxY)) {
			if ($maxY > $configMaxY) {
				\OCP\Util::writeLog('core', 'maxX reduced from ' . $maxY . ' to ' . $configMaxY, \OCP\Util::DEBUG);
				$maxY = $configMaxY;
			}
		}
		$this->maxY = $maxY;
		return $this;
	}

	/**
	 * set whether or not scalingup is enabled
	 * @param bool $scalingUp
	 * @return \OC\Preview $this
	 */
	public function setScalingup($scalingUp) {
		if ($this->getMaxScaleFactor() === 1) {
			$scalingUp = false;
		}
		$this->scalingUp = $scalingUp;
		return $this;
	}

	/**
	 * @param bool $keepAspect
	 * @return $this
	 */
	public function setKeepAspect($keepAspect) {
		$this->keepAspect = $keepAspect;
		return $this;
	}

	/**
	 * check if all parameters are valid
	 * @return bool
	 */
	public function isFileValid() {
		$file = $this->getFile();
		if ($file === '') {
			\OCP\Util::writeLog('core', 'No filename passed', \OCP\Util::DEBUG);
			return false;
		}

		if (!$this->fileView->file_exists($file)) {
			\OCP\Util::writeLog('core', 'File:"' . $file . '" not found', \OCP\Util::DEBUG);
			return false;
		}

		return true;
	}

	/**
	 * deletes previews of a file with specific x and y
	 * @return bool
	 */
	public function deletePreview() {
		$fileInfo = $this->getFileInfo();
		if($fileInfo !== null && $fileInfo !== false) {
			$fileId = $fileInfo->getId();

			$previewPath = $this->buildCachePath($fileId);
			return $this->userView->unlink($previewPath);
		}
		return false;
	}

	/**
	 * deletes all previews of a file
	 */
	public function deleteAllPreviews() {
		$toDelete = $this->getChildren();
		$toDelete[] = $this->getFileInfo();

		foreach ($toDelete as $delete) {
			if ($delete instanceof FileInfo) {
				/** @var \OCP\Files\FileInfo $delete */
				$fileId = $delete->getId();

				// getId() might return null, e.g. when the file is a
				// .ocTransferId*.part file from chunked file upload.
				if (!empty($fileId)) {
					$previewPath = $this->getPreviewPath($fileId);
					$this->userView->deleteAll($previewPath);
					$this->userView->rmdir($previewPath);
				}
			}
		}
	}

	/**
	 * Checks if thumbnail or bigger version of thumbnail of file is already cached
	 *
	 * @param int $fileId fileId of the original image
	 * @return string|false path to thumbnail if it exists or false
	 */
	public function isCached($fileId) {
		if (is_null($fileId)) {
			return false;
		}

		// This gives us a calculated path to a preview of asked dimensions
		// thumbnailFolder/fileId/my_image-<maxX>-<maxY>.png
		$preview = $this->buildCachePath($fileId);

		// This checks if a preview exists at that location
		if ($this->userView->file_exists($preview)) {
			return $preview;
		}

		return $this->isCachedBigger($fileId);
	}

	/**
	 * Checks if a bigger version of a file preview is cached and if not
	 * return the preview of max allowed dimensions
	 *
	 * @param int $fileId fileId of the original image
	 *
	 * @return string|false path to bigger thumbnail if it exists or false
	 */
	private function isCachedBigger($fileId) {

		if (is_null($fileId)) {
			return false;
		}

		$maxX = $this->getMaxX();

		//array for usable cached thumbnails
		// FIXME: Checking only the width could lead to issues
		$possibleThumbnails = $this->getPossibleThumbnails($fileId);

		foreach ($possibleThumbnails as $width => $path) {
			if ($width === 'max' || $width < $maxX) {
				continue;
			} else {
				return $path;
			}
		}

		// At this stage, we didn't find a preview, so if the folder is not empty,
		// we return the max preview we generated on the first run
		if ($possibleThumbnails) {
			return $possibleThumbnails['max'];
		}

		return false;
	}

	/**
	 * get possible bigger thumbnails of the given image
	 * @param int $fileId fileId of the original image
	 * @return array an array of paths to bigger thumbnails
	*/
	private function getPossibleThumbnails($fileId) {

		if (is_null($fileId)) {
			return array();
		}

		$previewPath = $this->getPreviewPath($fileId);

		$wantedAspectRatio = (float)($this->getMaxX() / $this->getMaxY());

		//array for usable cached thumbnails
		$possibleThumbnails = array();

		$allThumbnails = $this->userView->getDirectoryContent($previewPath);
		foreach ($allThumbnails as $thumbnail) {
			$name = rtrim($thumbnail['name'], '.png');
			// Always add the max preview to the array
			if (strpos($name, 'max')) {
				$possibleThumbnails['max'] = $thumbnail['path'];
				continue;
			}
			list($x, $y, $aspectRatio) = $this->getDimensionsFromFilename($name);

			if (abs($aspectRatio - $wantedAspectRatio) >= 0.000001
				|| $this->unscalable($x, $y)
			) {
				continue;
			}
			$possibleThumbnails[$x] = $thumbnail['path'];
		}

		ksort($possibleThumbnails);

		return $possibleThumbnails;
	}

	/**
	 * @param string $name
	 * @return array
	 */
	private function getDimensionsFromFilename($name) {
			$size = explode('-', $name);
			$x = (int) $size[0];
			$y = (int) $size[1];
			$aspectRatio = (float) ($x / $y);
			return array($x, $y, $aspectRatio);
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @return bool
	 */
	private function unscalable($x, $y) {

		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();
		$scalingUp = $this->getScalingUp();
		$maxScaleFactor = $this->getMaxScaleFactor();

		if ($x < $maxX || $y < $maxY) {
			if ($scalingUp) {
				$scalefactor = $maxX / $x;
				if ($scalefactor > $maxScaleFactor) {
					return true;
				}
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a preview of a file
	 *
	 * The cache is searched first and if nothing usable was found then a preview is
	 * generated by one of the providers
	 *
	 * @return \OCP\IImage
	 */
	public function getPreview() {
		if (!is_null($this->preview) && $this->preview->valid()) {
			return $this->preview;
		}

		$this->preview = null;
		$fileInfo = $this->getFileInfo();
		if ($fileInfo === null || $fileInfo === false) {
			return new \OC_Image();
		}

		$fileId = $fileInfo->getId();
		$cached = $this->isCached($fileId);
		if ($cached) {
			$this->getCachedPreview($fileId, $cached);
		}

		if (is_null($this->preview)) {
			$this->generatePreview($fileId);
		}

		// We still don't have a preview, so we generate an empty object which can't be displayed
		if (is_null($this->preview)) {
			$this->preview = new \OC_Image();
		}

		return $this->preview;
	}

	/**
	 * @param null|string $mimeType
	 * @throws NotFoundException
	 */
	public function showPreview($mimeType = null) {
		// Check if file is valid
		if($this->isFileValid() === false) {
			throw new NotFoundException('File not found.');
		}

		\OCP\Response::enableCaching(3600 * 24); // 24 hours
		if (is_null($this->preview)) {
			$this->getPreview();
		}
		if ($this->preview instanceof \OCP\IImage) {
			$this->preview->show($mimeType);
		}
	}

	/**
	 * Retrieves the preview from the cache and resizes it if necessary
	 *
	 * @param int $fileId fileId of the original image
	 * @param string $cached the path to the cached preview
	 */
	private function getCachedPreview($fileId, $cached) {
		$stream = $this->userView->fopen($cached, 'r');
		$this->preview = null;
		if ($stream) {
			$image = new \OC_Image();
			$image->loadFromFileHandle($stream);

			$this->preview = $image->valid() ? $image : null;

			$maxX = (int)$this->getMaxX();
			$maxY = (int)$this->getMaxY();
			$previewX = (int)$this->preview->width();
			$previewY = (int)$this->preview->height();

			if ($previewX !== $maxX && $previewY !== $maxY) {
				$this->resizeAndStore($fileId);
			}

			fclose($stream);
		}
	}

	/**
	 * Resizes, crops, fixes orientation and stores in the cache
	 *
	 * @param int $fileId fileId of the original image
	 */
	private function resizeAndStore($fileId) {
		// Resize and store
		$this->resizeAndCrop();
		// We save a copy in the cache to speed up future calls
		$cachePath = $this->buildCachePath($fileId);
		$this->userView->file_put_contents($cachePath, $this->preview->data());
	}

	/**
	 * resize, crop and fix orientation
	 *
	 * @param bool $max
	 */
	private function resizeAndCrop($max = false) {
		$image = $this->preview;

		list($x, $y, $scalingUp, $maxScaleFactor) = $this->getResizeData($max);

		if (!($image instanceof \OCP\IImage)) {
			\OCP\Util::writeLog('core', '$this->preview is not an instance of OC_Image', \OCP\Util::DEBUG);
			return;
		}

		$realX = (int)$image->width();
		$realY = (int)$image->height();

		// compute $maxY and $maxX using the aspect of the generated preview
		if ($this->keepAspect) {
			$ratio = $realX / $realY;
			if($x / $ratio < $y) {
				// width restricted
				$y = $x / $ratio;
			} else {
				$x = $y * $ratio;
			}
		}

		// The preview already has the asked dimensions
		if ($x === $realX && $y === $realY) {
			$this->preview = $image;
			return;
		}

		$factorX = $x / $realX;
		$factorY = $y / $realY;

		if ($factorX >= $factorY) {
			$factor = $factorX;
		} else {
			$factor = $factorY;
		}

		if ($scalingUp === false) {
			if ($factor > 1) {
				$factor = 1;
			}
		}

		if (!is_null($maxScaleFactor)) {
			if ($factor > $maxScaleFactor) {
				\OCP\Util::writeLog('core', 'scale factor reduced from ' . $factor . ' to ' . $maxScaleFactor, \OCP\Util::DEBUG);
				$factor = $maxScaleFactor;
			}
		}

		$newXSize = (int)($realX * $factor);
		$newYSize = (int)($realY * $factor);

		$image->preciseResize($newXSize, $newYSize);

		// The preview has been upscaled and now has the asked dimensions
		if ($newXSize === $x && $newYSize === $y) {
			$this->preview = $image;
			return;
		}

		// One dimension of the upscaled preview is too big
		if ($newXSize >= $x && $newYSize >= $y) {
			$cropX = floor(abs($x - $newXSize) * 0.5);
			//don't crop previews on the Y axis, this sucks if it's a document.
			//$cropY = floor(abs($y - $newYsize) * 0.5);
			$cropY = 0;

			$image->crop($cropX, $cropY, $x, $y);

			$this->preview = $image;
			return;
		}

		// One dimension of the upscaled preview is too small and we're allowed to scale up
		if (($newXSize < $x || $newYSize < $y) && $scalingUp) {
			if ($newXSize > $x) {
				$cropX = floor(($newXSize - $x) * 0.5);
				$image->crop($cropX, 0, $x, $newYSize);
			}

			if ($newYSize > $y) {
				$cropY = floor(($newYSize - $y) * 0.5);
				$image->crop(0, $cropY, $newXSize, $y);
			}

			$newXSize = (int)$image->width();
			$newYSize = (int)$image->height();

			//create transparent background layer
			$backgroundLayer = imagecreatetruecolor($x, $y);
			$white = imagecolorallocate($backgroundLayer, 255, 255, 255);
			imagefill($backgroundLayer, 0, 0, $white);

			$image = $image->resource();

			$mergeX = floor(abs($x - $newXSize) * 0.5);
			$mergeY = floor(abs($y - $newYSize) * 0.5);

			imagecopy($backgroundLayer, $image, $mergeX, $mergeY, 0, 0, $newXSize, $newYSize);

			//$black = imagecolorallocate(0,0,0);
			//imagecolortransparent($transparentlayer, $black);

			$image = new \OC_Image($backgroundLayer);

			$this->preview = $image;

			return;
		}
	}

	/**
	 * Returns data to be used to resize a preview
	 *
	 * @param $max
	 *
	 * @return array
	 */
	private function getResizeData($max) {
		if (!$max) {
			$x = $this->getMaxX();
			$y = $this->getMaxY();
			$scalingUp = $this->getScalingUp();
			$maxScaleFactor = $this->getMaxScaleFactor();
		} else {
			$x = $this->configMaxX;
			$y = $this->configMaxY;
			$scalingUp = false;
			$maxScaleFactor =1;
		}

		return [$x, $y, $scalingUp, $maxScaleFactor];
	}

	/**
	 * Returns the path to a preview based on its dimensions and aspect
	 *
	 * @param int $fileId
	 *
	 * @return string
	 */
	private function buildCachePath($fileId) {
		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();

		$previewPath = $this->getPreviewPath($fileId);
		$previewPath = $previewPath . strval($maxX) . '-' . strval($maxY);
		if ($this->keepAspect) {
			$previewPath .= '-with-aspect';
		}
		$previewPath .= '.png';

		return $previewPath;
	}

	/**
	 * @param int $fileId
	 *
	 * @return string
	 */
	private function getPreviewPath($fileId) {
		return $this->getThumbnailsFolder() . '/' . $fileId . '/';
	}

	/**
	 * Asks the provider to send a preview of the file of maximum dimensions
	 * and after saving it in the cache, it is then resized to the asked dimensions
	 *
	 * This is only called once in order to generate a large PNG of dimensions defined in the
	 * configuration file. We'll be able to quickly resize it later on.
	 * We never upscale the original conversion as this will be done later by the resizing operation
	 *
	 * @param int $fileId fileId of the original image
	 */
	private function generatePreview($fileId) {
		$file = $this->getFile();
		$preview = null;

		$previewProviders = \OC::$server->getPreviewManager()->getProviders();
			foreach ($previewProviders as $supportedMimeType => $providers) {
				if (!preg_match($supportedMimeType, $this->mimeType)) {
					continue;
				}

				foreach ($providers as $closure) {
					$provider = $closure();
					if (!($provider instanceof \OCP\Preview\IProvider)) {
						continue;
					}

					\OCP\Util::writeLog(
						'core', 'Generating preview for "' . $file . '" with "' . get_class($provider)
						. '"', \OCP\Util::DEBUG
					);

					/** @var $provider Provider */
					$preview = $provider->getThumbnail(
						$file, $this->configMaxX, $this->configMaxY, $scalingUp = false, $this->fileView
					);

					if (!($preview instanceof \OCP\IImage)) {
						continue;
					}

					$this->preview = $preview;
					$previewPath = $this->getPreviewPath($fileId);

					if ($this->userView->is_dir($this->getThumbnailsFolder() . '/') === false) {
						$this->userView->mkdir($this->getThumbnailsFolder() . '/');
					}

					if ($this->userView->is_dir($previewPath) === false) {
						$this->userView->mkdir($previewPath);
					}

					// This stores our large preview so that it can be used in subsequent resizing requests
					$this->storeMaxPreview($previewPath);

					break 2;
				}
			}

		// The providers have been kind enough to give us a preview
		if ($preview) {
			$this->resizeAndStore($fileId);
		}
	}

	/**
	 * Stores the max preview in the cache
	 *
	 * @param string $previewPath path to the preview
	 */
	private function storeMaxPreview($previewPath) {
		$maxPreview = false;
		$preview = $this->preview;

		$allThumbnails = $this->userView->getDirectoryContent($previewPath);
		// This is so that the cache doesn't need emptying when upgrading
		// Can be replaced by an upgrade script...
		foreach ($allThumbnails as $thumbnail) {
			$name = rtrim($thumbnail['name'], '.png');
			if (strpos($name, 'max')) {
				$maxPreview = true;
				break;
			}
		}
		// We haven't found the max preview, so we create it
		if (!$maxPreview) {
			// Most providers don't resize their thumbnails yet
			$this->resizeAndCrop(true);

			$maxX = $preview->width();
			$maxY = $preview->height();
			$previewPath = $previewPath . strval($maxX) . '-' . strval($maxY);
			$previewPath .= '-max.png';
			$this->userView->file_put_contents($previewPath, $preview->data());
		}
	}

	/**
	 * @param array $args
	 */
	public static function post_write($args) {
		self::post_delete($args, 'files/');
	}

	/**
	 * @param array $args
	 */
	public static function prepare_delete_files($args) {
		self::prepare_delete($args, 'files/');
	}

	/**
	 * @param array $args
	 * @param string $prefix
	 */
	public static function prepare_delete($args, $prefix='') {
		$path = $args['path'];
		if (substr($path, 0, 1) === '/') {
			$path = substr($path, 1);
		}

		$view = new \OC\Files\View('/' . \OC_User::getUser() . '/' . $prefix);

		$absPath = Files\Filesystem::normalizePath($view->getAbsolutePath($path));
		self::addPathToDeleteFileMapper($absPath, $view->getFileInfo($path));
		if ($view->is_dir($path)) {
			$children = self::getAllChildren($view, $path);
			self::$deleteChildrenMapper[$absPath] = $children;
		}
	}

	/**
	 * @param string $absolutePath
	 * @param \OCP\Files\FileInfo $info
	 */
	private static function addPathToDeleteFileMapper($absolutePath, $info) {
		self::$deleteFileMapper[$absolutePath] = $info;
	}

	/**
	 * @param \OC\Files\View $view
	 * @param string $path
	 * @return array
	 */
	private static function getAllChildren($view, $path) {
		$children = $view->getDirectoryContent($path);
		$childrensFiles = array();

		$fakeRootLength = strlen($view->getRoot());

		for ($i = 0; $i < count($children); $i++) {
			$child = $children[$i];

			$childsPath = substr($child->getPath(), $fakeRootLength);

			if ($view->is_dir($childsPath)) {
				$children = array_merge(
					$children,
					$view->getDirectoryContent($childsPath)
				);
			} else {
				$childrensFiles[] = $child;
			}
		}

		return $childrensFiles;
	}

	/**
	 * @param array $args
	 */
	public static function post_delete_files($args) {
		self::post_delete($args, 'files/');
	}

	/**
	 * @param array $args
	 * @param string $prefix
	 */
	public static function post_delete($args, $prefix='') {
		$path = Files\Filesystem::normalizePath($args['path']);

		$preview = new Preview(\OC_User::getUser(), $prefix, $path);
		$preview->deleteAllPreviews();
	}

}
