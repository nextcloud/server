<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
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

	const MODE_FILL = 'fill';
	const MODE_COVER = 'cover';

	//config
	private $maxScaleFactor;
	/** @var int maximum width allowed for a preview */
	private $configMaxWidth;
	/** @var int maximum height allowed for a preview */
	private $configMaxHeight;

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
	private $mode = self::MODE_FILL;

	//used to calculate the size of the preview to generate
	/** @var int $maxPreviewWidth max width a preview can have */
	private $maxPreviewWidth;
	/** @var int $maxPreviewHeight max height a preview can have */
	private $maxPreviewHeight;
	/** @var int $previewWidth calculated width of the preview we're looking for */
	private $previewWidth;
	/** @var int $previewHeight calculated height of the preview we're looking for */
	private $previewHeight;

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
	 *
	 * @param string $user userid - if no user is given, OC_User::getUser will be used
	 * @param string $root path of root
	 * @param string $file The path to the file where you want a thumbnail from
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the
	 *     shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the
	 *     shape of the image
	 * @param bool $scalingUp Disable/Enable upscaling of previews
	 *
	 * @throws \Exception
	 * @return mixed (bool / string)
	 *                    false if thumbnail does not exist
	 *                    path to thumbnail if thumbnail exists
	 */
	public function __construct(
		$user = '',
		$root = '/',
		$file = '', $maxX = 1,
		$maxY = 1,
		$scalingUp = true
	) {
		//init fileviews
		if ($user === '') {
			$user = \OC_User::getUser();
		}
		$this->fileView = new \OC\Files\View('/' . $user . '/' . $root);
		$this->userView = new \OC\Files\View('/' . $user);

		//set config
		$sysConfig = \OC::$server->getConfig();
		$this->configMaxWidth = $sysConfig->getSystemValue('preview_max_x', 2048);
		$this->configMaxHeight = $sysConfig->getSystemValue('preview_max_y', 2048);
		$this->maxScaleFactor = $sysConfig->getSystemValue('preview_max_scale_factor', 2);

		//save parameters
		$this->setFile($file);
		$this->setMaxX((int)$maxX);
		$this->setMaxY((int)$maxY);
		$this->setScalingUp($scalingUp);

		$this->preview = null;

		//check if there are preview backends
		if (!\OC::$server->getPreviewManager()
				->hasProviders()
			&& \OC::$server->getConfig()
				->getSystemValue('enable_previews', true)
		) {
			\OCP\Util::writeLog('core', 'No preview providers exist', \OCP\Util::ERROR);
			throw new \Exception('No preview providers');
		}
	}

	/**
	 * returns the path of the file you want a thumbnail from
	 *
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * returns the max width of the preview
	 *
	 * @return integer
	 */
	public function getMaxX() {
		return $this->maxX;
	}

	/**
	 * returns the max height of the preview
	 *
	 * @return integer
	 */
	public function getMaxY() {
		return $this->maxY;
	}

	/**
	 * returns whether or not scalingup is enabled
	 *
	 * @return bool
	 */
	public function getScalingUp() {
		return $this->scalingUp;
	}

	/**
	 * returns the name of the thumbnailfolder
	 *
	 * @return string
	 */
	public function getThumbnailsFolder() {
		return self::THUMBNAILS_FOLDER;
	}

	/**
	 * returns the max scale factor
	 *
	 * @return string
	 */
	public function getMaxScaleFactor() {
		return $this->maxScaleFactor;
	}

	/**
	 * returns the max width set in ownCloud's config
	 *
	 * @return integer
	 */
	public function getConfigMaxX() {
		return $this->configMaxWidth;
	}

	/**
	 * returns the max height set in ownCloud's config
	 *
	 * @return integer
	 */
	public function getConfigMaxY() {
		return $this->configMaxHeight;
	}

	/**
	 * Returns the FileInfo object associated with the file to preview
	 *
	 * @return false|Files\FileInfo|\OCP\Files\FileInfo
	 */
	protected function getFileInfo() {
		$absPath = $this->fileView->getAbsolutePath($this->file);
		$absPath = Files\Filesystem::normalizePath($absPath);
		if (array_key_exists($absPath, self::$deleteFileMapper)) {
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
	 * Sets the path of the file you want a preview of
	 *
	 * @param string $file
	 * @param \OCP\Files\FileInfo|null $info
	 *
	 * @return \OC\Preview
	 */
	public function setFile($file, $info = null) {
		$this->file = $file;
		$this->info = $info;

		if ($file !== '') {
			$this->getFileInfo();
			if ($this->info instanceof \OCP\Files\FileInfo) {
				$this->mimeType = $this->info->getMimetype();
			}
		}

		return $this;
	}

	/**
	 * Forces the use of a specific media type
	 *
	 * @param string $mimeType
	 */
	public function setMimetype($mimeType) {
		$this->mimeType = $mimeType;
	}

	/**
	 * Sets the max width of the preview. It's capped by the maximum allowed size set in the
	 * configuration
	 *
	 * @param int $maxX
	 *
	 * @throws \Exception
	 * @return \OC\Preview
	 */
	public function setMaxX($maxX = 1) {
		if ($maxX <= 0) {
			throw new \Exception('Cannot set width of 0 or smaller!');
		}
		$configMaxX = $this->getConfigMaxX();
		$maxX = $this->limitMaxDim($maxX, $configMaxX, 'maxX');
		$this->maxX = $maxX;

		return $this;
	}

	/**
	 * Sets the max height of the preview. It's capped by the maximum allowed size set in the
	 * configuration
	 *
	 * @param int $maxY
	 *
	 * @throws \Exception
	 * @return \OC\Preview
	 */
	public function setMaxY($maxY = 1) {
		if ($maxY <= 0) {
			throw new \Exception('Cannot set height of 0 or smaller!');
		}
		$configMaxY = $this->getConfigMaxY();
		$maxY = $this->limitMaxDim($maxY, $configMaxY, 'maxY');
		$this->maxY = $maxY;

		return $this;
	}

	/**
	 * Sets whether we're allowed to scale up when generating a preview. It's capped by the maximum
	 * allowed scale factor set in the configuration
	 *
	 * @param bool $scalingUp
	 *
	 * @return \OC\Preview
	 */
	public function setScalingup($scalingUp) {
		if ($this->getMaxScaleFactor() === 1) {
			$scalingUp = false;
		}
		$this->scalingUp = $scalingUp;

		return $this;
	}

	/**
	 * Set whether to cover or fill the specified dimensions
	 *
	 * @param string $mode
	 *
	 * @return \OC\Preview
	 */
	public function setMode($mode) {
		$this->mode = $mode;

		return $this;
	}

	/**
	 * Sets whether we need to generate a preview which keeps the aspect ratio of the original file
	 *
	 * @param bool $keepAspect
	 *
	 * @return \OC\Preview
	 */
	public function setKeepAspect($keepAspect) {
		$this->keepAspect = $keepAspect;

		return $this;
	}

	/**
	 * Makes sure we were given a file to preview and that it exists in the filesystem
	 *
	 * @return bool
	 */
	public function isFileValid() {
		$file = $this->getFile();
		if ($file === '') {
			\OCP\Util::writeLog('core', 'No filename passed', \OCP\Util::DEBUG);

			return false;
		}

		if (!$this->getFileInfo() instanceof FileInfo) {
			\OCP\Util::writeLog('core', 'File:"' . $file . '" not found', \OCP\Util::DEBUG);

			return false;
		}

		return true;
	}

	/**
	 * Deletes the preview of a file with specific width and height
	 *
	 * This should never delete the max preview, use deleteAllPreviews() instead
	 *
	 * @return bool
	 */
	public function deletePreview() {
		$fileInfo = $this->getFileInfo();
		if ($fileInfo !== null && $fileInfo !== false) {
			$fileId = $fileInfo->getId();

			$previewPath = $this->buildCachePath($fileId);
			if (!strpos($previewPath, 'max')) {
				return $this->userView->unlink($previewPath);
			}
		}

		return false;
	}

	/**
	 * Deletes all previews of a file
	 */
	public function deleteAllPreviews() {
		$thumbnailMount = $this->userView->getMount($this->getThumbnailsFolder());
		$propagator = $thumbnailMount->getStorage()->getPropagator();
		$propagator->beginBatch();

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
					$this->userView->rmdir($previewPath);
				}
			}
		}

		$propagator->commitBatch();
	}

	/**
	 * Checks if a preview matching the asked dimensions or a bigger version is already cached
	 *
	 *    * We first retrieve the size of the max preview since this is what we be used to create
	 * all our preview. If it doesn't exist we return false, so that it can be generated
	 *    * Using the dimensions of the max preview, we calculate what the size of the new
	 * thumbnail should be
	 *    * And finally, we look for a suitable candidate in the cache
	 *
	 * @param int $fileId fileId of the original file we need a preview of
	 *
	 * @return string|false path to the cached preview if it exists or false
	 */
	public function isCached($fileId) {
		if (is_null($fileId)) {
			return false;
		}

		/**
		 * Phase 1: Looking for the max preview
		 */
		$previewPath = $this->getPreviewPath($fileId);
		// We currently can't look for a single file due to bugs related to #16478
		$allThumbnails = $this->userView->getDirectoryContent($previewPath);
		list($maxPreviewWidth, $maxPreviewHeight) = $this->getMaxPreviewSize($allThumbnails);

		// Only use the cache if we have a max preview
		if (!is_null($maxPreviewWidth) && !is_null($maxPreviewHeight)) {

			/**
			 * Phase 2: Calculating the size of the preview we need to send back
			 */
			$this->maxPreviewWidth = $maxPreviewWidth;
			$this->maxPreviewHeight = $maxPreviewHeight;

			list($previewWidth, $previewHeight) = $this->simulatePreviewDimensions();
			if (empty($previewWidth) || empty($previewHeight)) {
				return false;
			}

			$this->previewWidth = $previewWidth;
			$this->previewHeight = $previewHeight;

			/**
			 * Phase 3: We look for a preview of the exact size
			 */
			// This gives us a calculated path to a preview of asked dimensions
			// thumbnailFolder/fileId/<maxX>-<maxY>(-max|-with-aspect).png
			$preview = $this->buildCachePath($fileId, $previewWidth, $previewHeight);

			// This checks if we have a preview of those exact dimensions in the cache
			if ($this->thumbnailSizeExists($allThumbnails, basename($preview))) {
				return $preview;
			}

			/**
			 * Phase 4: We look for a larger preview, matching the aspect ratio
			 */
			if (($this->getMaxX() >= $maxPreviewWidth)
				&& ($this->getMaxY() >= $maxPreviewHeight)
			) {
				// The preview we-re looking for is the exact size or larger than the max preview,
				// so return that
				return $this->buildCachePath($fileId, $maxPreviewWidth, $maxPreviewHeight);
			} else {
				// The last resort is to look for something bigger than what we've calculated,
				// but still smaller than the max preview
				return $this->isCachedBigger($fileId, $allThumbnails);
			}
		}

		return false;
	}

	/**
	 * Returns the dimensions of the max preview
	 *
	 * @param FileInfo[] $allThumbnails the list of all our cached thumbnails
	 *
	 * @return int[]
	 */
	private function getMaxPreviewSize($allThumbnails) {
		$maxPreviewX = null;
		$maxPreviewY = null;

		foreach ($allThumbnails as $thumbnail) {
			$name = $thumbnail['name'];
			if (strpos($name, 'max')) {
				list($maxPreviewX, $maxPreviewY) = $this->getDimensionsFromFilename($name);
				break;
			}
		}

		return [$maxPreviewX, $maxPreviewY];
	}

	/**
	 * Check if a specific thumbnail size is cached
	 *
	 * @param FileInfo[] $allThumbnails the list of all our cached thumbnails
	 * @param string $name
	 * @return bool
	 */
	private function thumbnailSizeExists(array $allThumbnails, $name) {

		foreach ($allThumbnails as $thumbnail) {
			if ($name === $thumbnail->getName()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines the size of the preview we should be looking for in the cache
	 *
	 * @return integer[]
	 */
	private function simulatePreviewDimensions() {
		$askedWidth = $this->getMaxX();
		$askedHeight = $this->getMaxY();

		if ($this->keepAspect) {
			list($newPreviewWidth, $newPreviewHeight) =
				$this->applyAspectRatio($askedWidth, $askedHeight);
		} else {
			list($newPreviewWidth, $newPreviewHeight) = $this->fixSize($askedWidth, $askedHeight);
		}

		return [(int)$newPreviewWidth, (int)$newPreviewHeight];
	}

	/**
	 * Resizes the boundaries to match the aspect ratio
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 *
	 * @param int $originalWidth
	 * @param int $originalHeight
	 * @return integer[]
	 */
	private function applyAspectRatio($askedWidth, $askedHeight, $originalWidth = 0, $originalHeight = 0) {
		if (!$originalWidth) {
			$originalWidth = $this->maxPreviewWidth;
		}
		if (!$originalHeight) {
			$originalHeight = $this->maxPreviewHeight;
		}
		$originalRatio = $originalWidth / $originalHeight;
		// Defines the box in which the preview has to fit
		$scaleFactor = $this->scalingUp ? $this->maxScaleFactor : 1;
		$askedWidth = min($askedWidth, $originalWidth * $scaleFactor);
		$askedHeight = min($askedHeight, $originalHeight * $scaleFactor);

		if ($askedWidth / $originalRatio < $askedHeight) {
			// width restricted
			$askedHeight = round($askedWidth / $originalRatio);
		} else {
			$askedWidth = round($askedHeight * $originalRatio);
		}

		return [(int)$askedWidth, (int)$askedHeight];
	}

	/**
	 * Resizes the boundaries to cover the area
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 * @param int $previewWidth
	 * @param int $previewHeight
	 * @return integer[]
	 */
	private function applyCover($askedWidth, $askedHeight, $previewWidth, $previewHeight) {
		$originalRatio = $previewWidth / $previewHeight;
		// Defines the box in which the preview has to fit
		$scaleFactor = $this->scalingUp ? $this->maxScaleFactor : 1;
		$askedWidth = min($askedWidth, $previewWidth * $scaleFactor);
		$askedHeight = min($askedHeight, $previewHeight * $scaleFactor);

		if ($askedWidth / $originalRatio > $askedHeight) {
			// height restricted
			$askedHeight = round($askedWidth / $originalRatio);
		} else {
			$askedWidth = round($askedHeight * $originalRatio);
		}

		return [(int)$askedWidth, (int)$askedHeight];
	}

	/**
	 * Makes sure an upscaled preview doesn't end up larger than the max dimensions defined in the
	 * config
	 *
	 * @param int $askedWidth
	 * @param int $askedHeight
	 *
	 * @return integer[]
	 */
	private function fixSize($askedWidth, $askedHeight) {
		if ($this->scalingUp) {
			$askedWidth = min($this->configMaxWidth, $askedWidth);
			$askedHeight = min($this->configMaxHeight, $askedHeight);
		}

		return [(int)$askedWidth, (int)$askedHeight];
	}

	/**
	 * Checks if a bigger version of a file preview is cached and if not
	 * return the preview of max allowed dimensions
	 *
	 * @param int $fileId fileId of the original image
	 * @param FileInfo[] $allThumbnails the list of all our cached thumbnails
	 *
	 * @return string path to bigger thumbnail
	 */
	private function isCachedBigger($fileId, $allThumbnails) {
		// This is used to eliminate any thumbnail narrower than what we need
		$maxX = $this->getMaxX();

		//array for usable cached thumbnails
		$possibleThumbnails = $this->getPossibleThumbnails($allThumbnails);

		foreach ($possibleThumbnails as $width => $path) {
			if ($width < $maxX) {
				continue;
			} else {
				return $path;
			}
		}

		// At this stage, we didn't find a preview, so we return the max preview
		return $this->buildCachePath($fileId, $this->maxPreviewWidth, $this->maxPreviewHeight);
	}

	/**
	 * Get possible bigger thumbnails of the given image with the proper aspect ratio
	 *
	 * @param FileInfo[] $allThumbnails the list of all our cached thumbnails
	 *
	 * @return string[] an array of paths to bigger thumbnails
	 */
	private function getPossibleThumbnails($allThumbnails) {
		if ($this->keepAspect) {
			$wantedAspectRatio = (float)($this->maxPreviewWidth / $this->maxPreviewHeight);
		} else {
			$wantedAspectRatio = (float)($this->getMaxX() / $this->getMaxY());
		}

		//array for usable cached thumbnails
		$possibleThumbnails = array();
		foreach ($allThumbnails as $thumbnail) {
			$name = rtrim($thumbnail['name'], '.png');
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
	 * Looks at the preview filename from the cache and extracts the size of the preview
	 *
	 * @param string $name
	 *
	 * @return array<int,int,float>
	 */
	private function getDimensionsFromFilename($name) {
		$size = explode('-', $name);
		$x = (int)$size[0];
		$y = (int)$size[1];
		$aspectRatio = (float)($x / $y);

		return array($x, $y, $aspectRatio);
	}

	/**
	 * @param int $x
	 * @param int $y
	 *
	 * @return bool
	 */
	private function unscalable($x, $y) {

		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();
		$scalingUp = $this->getScalingUp();
		$maxScaleFactor = $this->getMaxScaleFactor();

		if ($x < $maxX || $y < $maxY) {
			if ($scalingUp) {
				$scaleFactor = $maxX / $x;
				if ($scaleFactor > $maxScaleFactor) {
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
		if ($fileInfo === null || $fileInfo === false || !$fileInfo->isReadable()) {
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

		// We still don't have a preview, so we send back an empty object
		if (is_null($this->preview)) {
			$this->preview = new \OC_Image();
		}

		return $this->preview;
	}

	/**
	 * Sends the preview, including the headers to client which requested it
	 *
	 * @param null|string $mimeTypeForHeaders the media type to use when sending back the reply
	 *
	 * @throws NotFoundException
	 * @throws PreviewNotAvailableException
	 */
	public function showPreview($mimeTypeForHeaders = null) {
		// Check if file is valid
		if ($this->isFileValid() === false) {
			throw new NotFoundException('File not found.');
		}

		if (is_null($this->preview)) {
			$this->getPreview();
		}
		if ($this->preview instanceof \OCP\IImage) {
			if ($this->preview->valid()) {
				\OCP\Response::enableCaching(3600 * 24); // 24 hours
			} else {
				$this->getMimeIcon();
			}
			$this->preview->show($mimeTypeForHeaders);
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

			if (!is_null($this->preview)) {
				// Size of the preview we calculated
				$maxX = $this->previewWidth;
				$maxY = $this->previewHeight;
				// Size of the preview we retrieved from the cache
				$previewX = (int)$this->preview->width();
				$previewY = (int)$this->preview->height();

				// We don't have an exact match
				if ($previewX !== $maxX || $previewY !== $maxY) {
					$this->resizeAndStore($fileId);
				}
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
		$image = $this->preview;
		if (!($image instanceof \OCP\IImage)) {
			\OCP\Util::writeLog(
				'core', '$this->preview is not an instance of \OCP\IImage', \OCP\Util::DEBUG
			);

			return;
		}
		$previewWidth = (int)$image->width();
		$previewHeight = (int)$image->height();
		$askedWidth = $this->getMaxX();
		$askedHeight = $this->getMaxY();

		if ($this->mode === self::MODE_COVER) {
			list($askedWidth, $askedHeight) =
				$this->applyCover($askedWidth, $askedHeight, $previewWidth, $previewHeight);
		}

		/**
		 * Phase 1: If required, adjust boundaries to keep aspect ratio
		 */
		if ($this->keepAspect) {
			list($askedWidth, $askedHeight) =
				$this->applyAspectRatio($askedWidth, $askedHeight, $previewWidth, $previewHeight);
		}

		/**
		 * Phase 2: Resizes preview to try and match requirements.
		 * Takes the scaling ratio into consideration
		 */
		list($newPreviewWidth, $newPreviewHeight) = $this->scale(
			$image, $askedWidth, $askedHeight, $previewWidth, $previewHeight
		);

		// The preview has been resized and should now have the asked dimensions
		if ($newPreviewWidth === $askedWidth && $newPreviewHeight === $askedHeight) {
			$this->storePreview($fileId, $newPreviewWidth, $newPreviewHeight);

			return;
		}

		/**
		 * Phase 3: We're still not there yet, so we're clipping and filling
		 * to match the asked dimensions
		 */
		// It turns out the scaled preview is now too big, so we crop the image
		if ($newPreviewWidth >= $askedWidth && $newPreviewHeight >= $askedHeight) {
			$this->crop($image, $askedWidth, $askedHeight, $newPreviewWidth, $newPreviewHeight);
			$this->storePreview($fileId, $askedWidth, $askedHeight);

			return;
		}

		// At least one dimension of the scaled preview is too small,
		// so we fill the space with a transparent background
		if (($newPreviewWidth < $askedWidth || $newPreviewHeight < $askedHeight)) {
			$this->cropAndFill(
				$image, $askedWidth, $askedHeight, $newPreviewWidth, $newPreviewHeight
			);
			$this->storePreview($fileId, $askedWidth, $askedHeight);

			return;
		}

		// The preview is smaller, but we can't touch it
		$this->storePreview($fileId, $newPreviewWidth, $newPreviewHeight);
	}

	/**
	 * Calculates the new dimensions of the preview
	 *
	 * The new dimensions can be larger or smaller than the ones of the preview we have to resize
	 *
	 * @param \OCP\IImage $image
	 * @param int $askedWidth
	 * @param int $askedHeight
	 * @param int $previewWidth
	 * @param int $previewHeight
	 *
	 * @return int[]
	 */
	private function scale($image, $askedWidth, $askedHeight, $previewWidth, $previewHeight) {
		$scalingUp = $this->getScalingUp();
		$maxScaleFactor = $this->getMaxScaleFactor();

		$factorX = $askedWidth / $previewWidth;
		$factorY = $askedHeight / $previewHeight;

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

		// We cap when upscaling
		if (!is_null($maxScaleFactor)) {
			if ($factor > $maxScaleFactor) {
				\OCP\Util::writeLog(
					'core', 'scale factor reduced from ' . $factor . ' to ' . $maxScaleFactor,
					\OCP\Util::DEBUG
				);
				$factor = $maxScaleFactor;
			}
		}

		$newPreviewWidth = round($previewWidth * $factor);
		$newPreviewHeight = round($previewHeight * $factor);

		$image->preciseResize($newPreviewWidth, $newPreviewHeight);
		$this->preview = $image;

		return [$newPreviewWidth, $newPreviewHeight];
	}

	/**
	 * Crops a preview which is larger than the dimensions we've received
	 *
	 * @param \OCP\IImage $image
	 * @param int $askedWidth
	 * @param int $askedHeight
	 * @param int $previewWidth
	 * @param int $previewHeight
	 */
	private function crop($image, $askedWidth, $askedHeight, $previewWidth, $previewHeight = null) {
		$cropX = floor(abs($askedWidth - $previewWidth) * 0.5);
		//don't crop previews on the Y axis, this sucks if it's a document.
		//$cropY = floor(abs($y - $newPreviewHeight) * 0.5);
		$cropY = 0;
		$image->crop($cropX, $cropY, $askedWidth, $askedHeight);
		$this->preview = $image;
	}

	/**
	 * Crops an image if it's larger than the dimensions we've received and fills the empty space
	 * with a transparent background
	 *
	 * @param \OCP\IImage $image
	 * @param int $askedWidth
	 * @param int $askedHeight
	 * @param int $previewWidth
	 * @param int $previewHeight
	 */
	private function cropAndFill($image, $askedWidth, $askedHeight, $previewWidth, $previewHeight) {
		if ($previewWidth > $askedWidth) {
			$cropX = floor(($previewWidth - $askedWidth) * 0.5);
			$image->crop($cropX, 0, $askedWidth, $previewHeight);
			$previewWidth = $askedWidth;
		}

		if ($previewHeight > $askedHeight) {
			$cropY = floor(($previewHeight - $askedHeight) * 0.5);
			$image->crop(0, $cropY, $previewWidth, $askedHeight);
			$previewHeight = $askedHeight;
		}

		// Creates a transparent background
		$backgroundLayer = imagecreatetruecolor($askedWidth, $askedHeight);
		imagealphablending($backgroundLayer, false);
		$transparency = imagecolorallocatealpha($backgroundLayer, 0, 0, 0, 127);
		imagefill($backgroundLayer, 0, 0, $transparency);
		imagesavealpha($backgroundLayer, true);

		$image = $image->resource();

		$mergeX = floor(abs($askedWidth - $previewWidth) * 0.5);
		$mergeY = floor(abs($askedHeight - $previewHeight) * 0.5);

		// Pastes the preview on top of the background
		imagecopy(
			$backgroundLayer, $image, $mergeX, $mergeY, 0, 0, $previewWidth,
			$previewHeight
		);

		$image = new \OC_Image($backgroundLayer);

		$this->preview = $image;
	}

	/**
	 * Saves a preview in the cache to speed up future calls
	 *
	 * Do not nullify the preview as it might send the whole process in a loop
	 *
	 * @param int $fileId fileId of the original image
	 * @param int $previewWidth
	 * @param int $previewHeight
	 */
	private function storePreview($fileId, $previewWidth, $previewHeight) {
		if (empty($previewWidth) || empty($previewHeight)) {
			\OCP\Util::writeLog(
				'core', 'Cannot save preview of dimension ' . $previewWidth . 'x' . $previewHeight,
				\OCP\Util::DEBUG
			);

		} else {
			$cachePath = $this->buildCachePath($fileId, $previewWidth, $previewHeight);
			$this->userView->file_put_contents($cachePath, $this->preview->data());
		}
	}

	/**
	 * Returns the path to a preview based on its dimensions and aspect
	 *
	 * @param int $fileId
	 * @param int|null $maxX
	 * @param int|null $maxY
	 *
	 * @return string
	 */
	private function buildCachePath($fileId, $maxX = null, $maxY = null) {
		if (is_null($maxX)) {
			$maxX = $this->getMaxX();
		}
		if (is_null($maxY)) {
			$maxY = $this->getMaxY();
		}

		$previewPath = $this->getPreviewPath($fileId);
		$previewPath = $previewPath . strval($maxX) . '-' . strval($maxY);
		$isMaxPreview =
			($maxX === $this->maxPreviewWidth && $maxY === $this->maxPreviewHeight) ? true : false;
		if ($isMaxPreview) {
			$previewPath .= '-max';
		}
		if ($this->keepAspect && !$isMaxPreview) {
			$previewPath .= '-with-aspect';
		}
		if ($this->mode === self::MODE_COVER) {
			$previewPath .= '-cover';
		}
		$previewPath .= '.png';

		return $previewPath;
	}

	/**
	 * Returns the path to the folder where the previews are stored, identified by the fileId
	 *
	 * @param int $fileId
	 *
	 * @return string
	 */
	private function getPreviewPath($fileId) {
		return $this->getThumbnailsFolder() . '/' . $fileId . '/';
	}

	/**
	 * Asks the provider to send a preview of the file which respects the maximum dimensions
	 * defined in the configuration and after saving it in the cache, it is then resized to the
	 * asked dimensions
	 *
	 * This is only called once in order to generate a large PNG of dimensions defined in the
	 * configuration file. We'll be able to quickly resize it later on.
	 * We never upscale the original conversion as this will be done later by the resizing
	 * operation
	 *
	 * @param int $fileId fileId of the original image
	 */
	private function generatePreview($fileId) {
		$file = $this->getFile();
		$preview = null;

		$previewProviders = \OC::$server->getPreviewManager()
			->getProviders();
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
					$file, $this->configMaxWidth, $this->configMaxHeight, $scalingUp = false,
					$this->fileView
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
	 * Defines the media icon, for the media type of the original file, as the preview
	 * @throws PreviewNotAvailableException
	 */
	private function getMimeIcon() {
		$image = new \OC_Image();
		$mimeIconWebPath = \OC::$server->getMimeTypeDetector()->mimeTypeIcon($this->mimeType);
		if (empty(\OC::$WEBROOT)) {
			$mimeIconServerPath = \OC::$SERVERROOT . $mimeIconWebPath;
		} else {
			$mimeIconServerPath = str_replace(\OC::$WEBROOT, \OC::$SERVERROOT, $mimeIconWebPath);
		}
		// we can't load SVGs into an image
		if (substr($mimeIconWebPath, -4) === '.svg') {
			throw new PreviewNotAvailableException('SVG mimetype cannot be rendered');
		}
		$image->loadFromFile($mimeIconServerPath);

		$this->preview = $image;
	}

	/**
	 * Stores the max preview in the cache
	 *
	 * @param string $previewPath path to the preview
	 */
	private function storeMaxPreview($previewPath) {
		$maxPreviewExists = false;
		$preview = $this->preview;

		$allThumbnails = $this->userView->getDirectoryContent($previewPath);
		// This is so that the cache doesn't need emptying when upgrading
		// Can be replaced by an upgrade script...
		foreach ($allThumbnails as $thumbnail) {
			$name = rtrim($thumbnail['name'], '.png');
			if (strpos($name, 'max')) {
				$maxPreviewExists = true;
				break;
			}
		}
		// We haven't found the max preview, so we create it
		if (!$maxPreviewExists) {
			$previewWidth = $preview->width();
			$previewHeight = $preview->height();
			$previewPath = $previewPath . strval($previewWidth) . '-' . strval($previewHeight);
			$previewPath .= '-max.png';
			$this->userView->file_put_contents($previewPath, $preview->data());
			$this->maxPreviewWidth = $previewWidth;
			$this->maxPreviewHeight = $previewHeight;
		}
	}

	/**
	 * Limits a dimension to the maximum dimension provided as argument
	 *
	 * @param int $dim
	 * @param int $maxDim
	 * @param string $dimName
	 *
	 * @return integer
	 */
	private function limitMaxDim($dim, $maxDim, $dimName) {
		if (!is_null($maxDim)) {
			if ($dim > $maxDim) {
				\OCP\Util::writeLog(
					'core', $dimName . ' reduced from ' . $dim . ' to ' . $maxDim, \OCP\Util::DEBUG
				);
				$dim = $maxDim;
			}
		}

		return $dim;
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
	public static function prepare_delete(array $args, $prefix = '') {
		$path = $args['path'];
		if (substr($path, 0, 1) === '/') {
			$path = substr($path, 1);
		}

		$view = new \OC\Files\View('/' . \OC_User::getUser() . '/' . $prefix);

		$absPath = Files\Filesystem::normalizePath($view->getAbsolutePath($path));
		$fileInfo = $view->getFileInfo($path);
		if ($fileInfo === false) {
			return;
		}
		self::addPathToDeleteFileMapper($absPath, $fileInfo);
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
	 *
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
	 */
	public static function post_delete_versions($args) {
		self::post_delete($args, 'files/');
	}

	/**
	 * @param array $args
	 * @param string $prefix
	 */
	public static function post_delete($args, $prefix = '') {
		$path = Files\Filesystem::normalizePath($args['path']);

		$preview = new Preview(\OC_User::getUser(), $prefix, $path);
		$preview->deleteAllPreviews();
	}

}
