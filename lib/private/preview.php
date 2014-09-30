<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 * Thumbnails:
 * structure of filename:
 * /data/user/thumbnails/pathhash/x-y.png
 *
 */
namespace OC;

use OC\Preview\Provider;
use OCP\Files\NotFoundException;

require_once 'preview/image.php';
require_once 'preview/movies.php';
require_once 'preview/mp3.php';
require_once 'preview/pdf.php';
require_once 'preview/svg.php';
require_once 'preview/txt.php';
require_once 'preview/office.php';
require_once 'preview/tiff.php';

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

	/**
	 * preview images object
	 *
	 * @var \OC_Image
	 */
	private $preview;

	//preview providers
	static private $providers = array();
	static private $registeredProviders = array();
	static private $enabledProviders = array();

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
		$this->configMaxX = \OC_Config::getValue('preview_max_x', null);
		$this->configMaxY = \OC_Config::getValue('preview_max_y', null);
		$this->maxScaleFactor = \OC_Config::getValue('preview_max_scale_factor', 2);

		//save parameters
		$this->setFile($file);
		$this->setMaxX($maxX);
		$this->setMaxY($maxY);
		$this->setScalingUp($scalingUp);

		$this->preview = null;

		//check if there are preview backends
		if (empty(self::$providers)) {
			self::initProviders();
		}

		if (empty(self::$providers)) {
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
	 * set the path of the file you want a thumbnail from
	 * @param string $file
	 * @return \OC\Preview $this
	 */
	public function setFile($file) {
		$this->file = $file;
		$this->info = null;
		if ($file !== '') {
			$this->getFileInfo();
			if($this->info !== null && $this->info !== false) {
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
				\OC_Log::write('core', 'maxX reduced from ' . $maxX . ' to ' . $configMaxX, \OC_Log::DEBUG);
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
				\OC_Log::write('core', 'maxX reduced from ' . $maxY . ' to ' . $configMaxY, \OC_Log::DEBUG);
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
			\OC_Log::write('core', 'No filename passed', \OC_Log::DEBUG);
			return false;
		}

		if (!$this->fileView->file_exists($file)) {
			\OC_Log::write('core', 'File:"' . $file . '" not found', \OC_Log::DEBUG);
			return false;
		}

		return true;
	}

	/**
	 * deletes previews of a file with specific x and y
	 * @return bool
	 */
	public function deletePreview() {
		$file = $this->getFile();

		$fileInfo = $this->getFileInfo($file);
		if($fileInfo !== null && $fileInfo !== false) {
			$fileId = $fileInfo->getId();

			$previewPath = $this->buildCachePath($fileId);
			return $this->userView->unlink($previewPath);
		}
		return false;
	}

	/**
	 * deletes all previews of a file
	 * @return bool
	 */
	public function deleteAllPreviews() {
		$file = $this->getFile();

		$fileInfo = $this->getFileInfo($file);
		if($fileInfo !== null && $fileInfo !== false) {
			$fileId = $fileInfo->getId();

			$previewPath = $this->getPreviewPath($fileId);
			$this->userView->deleteAll($previewPath);
			return $this->userView->rmdir($previewPath);
		}
		return false;
	}

	/**
	 * check if thumbnail or bigger version of thumbnail of file is cached
	 * @param int $fileId fileId of the original image
	 * @return string|false path to thumbnail if it exists or false
	 */
	public function isCached($fileId) {
		if (is_null($fileId)) {
			return false;
		}

		$preview = $this->buildCachePath($fileId);

		//does a preview with the wanted height and width already exist?
		if ($this->userView->file_exists($preview)) {
			return $preview;
		}

		return $this->isCachedBigger($fileId);
	}

	/**
	 * check if a bigger version of thumbnail of file is cached
	 * @param int $fileId fileId of the original image
	 * @return string|false path to bigger thumbnail if it exists or false
	*/
	private function isCachedBigger($fileId) {

		if (is_null($fileId)) {
			return false;
		}

		// in order to not loose quality we better generate aspect preserving previews from the original file
		if ($this->keepAspect) {
			return false;
		}

		$maxX = $this->getMaxX();

		//array for usable cached thumbnails
		$possibleThumbnails = $this->getPossibleThumbnails($fileId);

		foreach ($possibleThumbnails as $width => $path) {
			if ($width < $maxX) {
				continue;
			} else {
				return $path;
			}
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

		$wantedAspectRatio = (float) ($this->getMaxX() / $this->getMaxY());

		//array for usable cached thumbnails
		$possibleThumbnails = array();

		$allThumbnails = $this->userView->getDirectoryContent($previewPath);
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
	 * return a preview of a file
	 * @return \OC_Image
	 */
	public function getPreview() {
		if (!is_null($this->preview) && $this->preview->valid()) {
			return $this->preview;
		}

		$this->preview = null;
		$file = $this->getFile();
		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();
		$scalingUp = $this->getScalingUp();

		$fileInfo = $this->getFileInfo($file);
		if($fileInfo === null || $fileInfo === false) {
			return new \OC_Image();
		}
		$fileId = $fileInfo->getId();

		$cached = $this->isCached($fileId);
		if ($cached) {
			$stream = $this->userView->fopen($cached, 'r');
			$this->preview = null;
			if ($stream) {
				$image = new \OC_Image();
				$image->loadFromFileHandle($stream);
				$this->preview = $image->valid() ? $image : null;

				$this->resizeAndCrop();
				fclose($stream);
			}
		}

		if (is_null($this->preview)) {
			$preview = null;

			foreach (self::$providers as $supportedMimeType => $provider) {
				if (!preg_match($supportedMimeType, $this->mimeType)) {
					continue;
				}

				\OC_Log::write('core', 'Generating preview for "' . $file . '" with "' . get_class($provider) . '"', \OC_Log::DEBUG);

				/** @var $provider Provider */
				$preview = $provider->getThumbnail($file, $maxX, $maxY, $scalingUp, $this->fileView);

				if (!($preview instanceof \OC_Image)) {
					continue;
				}

				$this->preview = $preview;
				$this->resizeAndCrop();

				$previewPath = $this->getPreviewPath($fileId);
				$cachePath = $this->buildCachePath($fileId);

				if ($this->userView->is_dir($this->getThumbnailsFolder() . '/') === false) {
					$this->userView->mkdir($this->getThumbnailsFolder() . '/');
				}

				if ($this->userView->is_dir($previewPath) === false) {
					$this->userView->mkdir($previewPath);
				}

				$this->userView->file_put_contents($cachePath, $preview->data());

				break;
			}
		}

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
		if ($this->preview instanceof \OC_Image) {
			$this->preview->show($mimeType);
		}
	}

	/**
	 * resize, crop and fix orientation
	 * @return void
	 */
	private function resizeAndCrop() {
		$image = $this->preview;
		$x = $this->getMaxX();
		$y = $this->getMaxY();
		$scalingUp = $this->getScalingUp();
		$maxScaleFactor = $this->getMaxScaleFactor();

		if (!($image instanceof \OC_Image)) {
			\OC_Log::write('core', '$this->preview is not an instance of OC_Image', \OC_Log::DEBUG);
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
				\OC_Log::write('core', 'scale factor reduced from ' . $factor . ' to ' . $maxScaleFactor, \OC_Log::DEBUG);
				$factor = $maxScaleFactor;
			}
		}

		$newXSize = (int)($realX * $factor);
		$newYSize = (int)($realY * $factor);

		$image->preciseResize($newXSize, $newYSize);

		if ($newXSize === $x && $newYSize === $y) {
			$this->preview = $image;
			return;
		}

		if ($newXSize >= $x && $newYSize >= $y) {
			$cropX = floor(abs($x - $newXSize) * 0.5);
			//don't crop previews on the Y axis, this sucks if it's a document.
			//$cropY = floor(abs($y - $newYsize) * 0.5);
			$cropY = 0;

			$image->crop($cropX, $cropY, $x, $y);

			$this->preview = $image;
			return;
		}

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
	 * Register a new preview provider to be used
	 * @param $class
	 * @param array $options
	 */
	public static function registerProvider($class, $options = array()) {
		/**
		 * Only register providers that have been explicitly enabled
		 *
		 * The following providers are enabled by default:
		 *  - OC\Preview\Image
		 *  - OC\Preview\MP3
		 *  - OC\Preview\TXT
		 *  - OC\Preview\MarkDown
		 *
		 * The following providers are disabled by default due to performance or privacy concerns:
		 *  - OC\Preview\MSOfficeDoc
		 *  - OC\Preview\MSOffice2003
		 *  - OC\Preview\MSOffice2007
		 *  - OC\Preview\OpenDocument
		 *  - OC\Preview\StarOffice
 		 *  - OC\Preview\SVG
		 *  - OC\Preview\Movies
		 *  - OC\Preview\PDF
		 *  - OC\Preview\Tiff
		 */
		if(empty(self::$enabledProviders)) {
			self::$enabledProviders = \OC::$server->getConfig()->getSystemValue('enabledPreviewProviders', array(
				'OC\Preview\Image',
				'OC\Preview\MP3',
				'OC\Preview\TXT',
				'OC\Preview\MarkDown',
			));
		}

		if(in_array($class, self::$enabledProviders)) {
			self::$registeredProviders[] = array('class' => $class, 'options' => $options);
		}
	}

	/**
	 * create instances of all the registered preview providers
	 * @return void
	 */
	private static function initProviders() {
		if (!\OC::$server->getConfig()->getSystemValue('enable_previews', true)) {
			self::$providers = array();
			return;
		}

		if (count(self::$providers) > 0) {
			return;
		}

		foreach (self::$registeredProviders as $provider) {
			$class = $provider['class'];
			$options = $provider['options'];

			/** @var $object Provider */
			$object = new $class($options);
			self::$providers[$object->getMimeType()] = $object;
		}

		$keys = array_map('strlen', array_keys(self::$providers));
		array_multisort($keys, SORT_DESC, self::$providers);

	}

	public static function post_write($args) {
		self::post_delete($args, 'files/');
	}

	public static function prepare_delete_files($args) {
		self::prepare_delete($args, 'files/');
	}

	public static function prepare_delete($args, $prefix='') {
		$path = $args['path'];
		if (substr($path, 0, 1) === '/') {
			$path = substr($path, 1);
		}

		$view = new \OC\Files\View('/' . \OC_User::getUser() . '/' . $prefix);
		$info = $view->getFileInfo($path);

		\OC\Preview::$deleteFileMapper = array_merge(
			\OC\Preview::$deleteFileMapper,
			array(
				Files\Filesystem::normalizePath($view->getAbsolutePath($path)) => $info,
			)
		);
	}

	public static function post_delete_files($args) {
		self::post_delete($args, 'files/');
	}

	public static function post_delete($args, $prefix='') {
		$path = Files\Filesystem::normalizePath($args['path']);

		$preview = new Preview(\OC_User::getUser(), $prefix, $path);
		$preview->deleteAllPreviews();
	}

	/**
	 * Check if a preview can be generated for a file
	 *
	 * @param \OC\Files\FileInfo $file
	 * @return bool
	 */
	public static function isAvailable($file) {
		if (!\OC_Config::getValue('enable_previews', true)) {
			return false;
		}

		//check if there are preview backends
		if (empty(self::$providers)) {
			self::initProviders();
		}

		foreach (self::$providers as $supportedMimeType => $provider) {
			/**
			 * @var \OC\Preview\Provider $provider
			 */
			if (preg_match($supportedMimeType, $file->getMimetype())) {
				return $provider->isAvailable($file);
			}
		}
		return false;
	}

	/**
	 * @param string $mimeType
	 * @return bool
	 */
	public static function isMimeSupported($mimeType) {
		if (!\OC_Config::getValue('enable_previews', true)) {
			return false;
		}

		//check if there are preview backends
		if (empty(self::$providers)) {
			self::initProviders();
		}

		foreach (self::$providers as $supportedMimeType => $provider) {
			if (preg_match($supportedMimeType, $mimeType)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param int $fileId
	 * @return string
	 */
	private function buildCachePath($fileId) {
		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();

		$previewPath = $this->getPreviewPath($fileId);
		$preview = $previewPath . strval($maxX) . '-' . strval($maxY);
		if ($this->keepAspect) {
			$preview .= '-with-aspect';
		}
		$preview .= '.png';

		return $preview;
	}


	/**
	 * @param int $fileId
	 * @return string
	 */
	private function getPreviewPath($fileId) {
		return $this->getThumbnailsFolder() . '/' . $fileId . '/';
	}
}
