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

require_once('preview/images.php');
require_once('preview/movies.php');
require_once('preview/mp3.php');
require_once('preview/pdf.php');
require_once('preview/svg.php');
require_once('preview/txt.php');
require_once('preview/unknown.php');
require_once('preview/office.php');

class Preview {
	//the thumbnail folder
	const THUMBNAILS_FOLDER = 'thumbnails';

	//config
	private $max_scale_factor;
	private $max_x;
	private $max_y;

	//fileview object
	private $fileview = null;
	private $userview = null;

	//vars
	private $file;
	private $maxX;
	private $maxY;
	private $scalingup;

	//preview images object
	private $preview;

	//preview providers
	static private $providers = array();
	static private $registeredProviders = array();

	/**
	 * @brief check if thumbnail or bigger version of thumbnail of file is cached
	 * @param $user userid - if no user is given, OC_User::getUser will be used
	 * @param $root path of root
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @return mixed (bool / string) 
	 *					false if thumbnail does not exist
	 *					path to thumbnail if thumbnail exists
	*/
	public function __construct($user='', $root='/', $file='', $maxX=1, $maxY=1, $scalingup=true) {
		//set config
		$this->max_x = \OC_Config::getValue('preview_max_x', null);
		$this->max_y = \OC_Config::getValue('preview_max_y', null);
		$this->max_scale_factor = \OC_Config::getValue('preview_max_scale_factor', 10);

		//save parameters
		$this->setFile($file);
		$this->setMaxX($maxX);
		$this->setMaxY($maxY);
		$this->setScalingUp($scalingup);

		//init fileviews
		if($user === ''){
			$user = OC_User::getUser();
		}
		$this->fileview = new \OC\Files\View('/' . $user . '/' . $root);
		$this->userview = new \OC\Files\View('/' . $user);
		
		$this->preview = null;

		//check if there are preview backends
		if(empty(self::$providers)) {
			self::initProviders();
		}

		if(empty(self::$providers)) {
			\OC_Log::write('core', 'No preview providers exist', \OC_Log::ERROR);
			throw new \Exception('No preview providers');
		}
	}

	/**
	 * @brief returns the path of the file you want a thumbnail from
	 * @return string
	*/
	public function	getFile() {
		return $this->file;
	}

	/**
	 * @brief returns the max width of the preview
	 * @return integer
	*/
	public function getMaxX() {
		return $this->maxX;
	}

	/**
	 * @brief returns the max height of the preview
	 * @return integer
	*/
	public function getMaxY() {
		return $this->maxY;
	}

	/**
	 * @brief returns whether or not scalingup is enabled
	 * @return bool
	*/
	public function getScalingup() {
		return $this->scalingup;
	}

	/**
	 * @brief returns the name of the thumbnailfolder
	 * @return string
	*/
	public function getThumbnailsFolder() {
		return self::THUMBNAILS_FOLDER;
	}

	/**
	 * @brief returns the max scale factor
	 * @return integer
	*/
	public function getMaxScaleFactor() {
		return $this->max_scale_factor;
	}

	/**
	 * @brief returns the max width set in ownCloud's config
	 * @return integer
	*/
	public function getConfigMaxX() {
		return $this->max_x;
	}

	/**
	 * @brief returns the max height set in ownCloud's config
	 * @return integer
	*/
	public function getConfigMaxY() {
		return $this->max_y;
	}

	/**
	 * @brief set the path of the file you want a thumbnail from
	 * @return $this
	*/
	public function setFile($file) {
		$this->file = $file;
		return $this;
	}

	/**
	 * @brief set the the max width of the preview
	 * @return $this
	*/
	public function setMaxX($maxX=1) {
		if($maxX === 0) {
			throw new \Exception('Cannot set width of 0!');
		}
		$configMaxX = $this->getConfigMaxX();
		if(!is_null($configMaxX)) {
			if($maxX > $configMaxX) {
				\OC_Log::write('core', 'maxX reduced from ' . $maxX . ' to ' . $configMaxX, \OC_Log::DEBUG);
				$maxX = $configMaxX;
			}
		}
		$this->maxX = $maxX;
		return $this;
	}

	/**
	 * @brief set the the max height of the preview
	 * @return $this
	*/
	public function setMaxY($maxY=1) {
		if($maxY === 0) {
			throw new \Exception('Cannot set height of 0!');
		}
		$configMaxY = $this->getConfigMaxY();
		if(!is_null($configMaxY)) {
			if($maxY > $configMaxY) {
				\OC_Log::write('core', 'maxX reduced from ' . $maxY . ' to ' . $configMaxY, \OC_Log::DEBUG);
				$maxY = $configMaxY;
			}
		}
		$this->maxY = $maxY;
		return $this;
	}

	/**
	 * @brief set whether or not scalingup is enabled
	 * @return $this
	*/
	public function setScalingup($scalingup) {
		if($this->getMaxScaleFactor() === 1) {
			$scalingup = false;
		}
		$this->scalingup = $scalingup;
		return $this;
	}

	/**
	 * @brief check if all parameters are valid
	 * @return integer
	*/
	public function isFileValid() {
		$file = $this->getFile();
		if($file === '') {
			\OC_Log::write('core', 'No filename passed', \OC_Log::ERROR);
			return false;
		}

		if(!$this->fileview->file_exists($file)) {
			\OC_Log::write('core', 'File:"' . $file . '" not found', \OC_Log::ERROR);
			return false;
		}

		return true;
	}

	/**
	 * @brief deletes previews of a file with specific x and y
	 * @return bool
	*/
	public function deletePreview() {
		$file = $this->getFile();

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];

		$previewpath = $this->getThumbnailsFolder() . '/' . $fileid . '/' . $this->getMaxX() . '-' . $this->getMaxY() . '.png';
		$this->userview->unlink($previewpath);
		return $this->userview->file_exists($previewpath);
	}

	/**
	 * @brief deletes all previews of a file
	 * @return bool
	*/
	public function deleteAllPreviews() {
		$file = $this->getFile();

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];
		
		$previewpath = $this->getThumbnailsFolder() . '/' . $fileid . '/';
		$this->userview->deleteAll($previewpath);
		$this->userview->rmdir($previewpath);
		return $this->userview->is_dir($previewpath);
	}

	/**
	 * @brief check if thumbnail or bigger version of thumbnail of file is cached
	 * @return mixed (bool / string) 
	 *				false if thumbnail does not exist
	 *				path to thumbnail if thumbnail exists
	*/
	private function isCached() {
		$file = $this->getFile();
		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();
		$scalingup = $this->getScalingup();
		$maxscalefactor = $this->getMaxScaleFactor();

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];

		$previewpath = $this->getThumbnailsFolder() . '/' . $fileid . '/';
		if(!$this->userview->is_dir($previewpath)) {
			return false;
		}

		//does a preview with the wanted height and width already exist?
		if($this->userview->file_exists($previewpath . $maxX . '-' . $maxY . '.png')) {
			return $previewpath . $maxX . '-' . $maxY . '.png';
		}

		$wantedaspectratio = $maxX / $maxY;

		//array for usable cached thumbnails
		$possiblethumbnails = array();

		$allthumbnails = $this->userview->getDirectoryContent($previewpath);
		foreach($allthumbnails as $thumbnail) {
			$size = explode('-', $thumbnail['name']);
			$x = $size[0];
			$y = $size[1];

			$aspectratio = $x / $y;
			if($aspectratio != $wantedaspectratio) {
				continue;
			}

			if($x < $maxX || $y < $maxY) {
				if($scalingup) {
					$scalefactor = $maxX / $x;
					if($scalefactor > $maxscalefactor) {
						continue;
					}
				}else{
					continue;
				}
			}
			$possiblethumbnails[$x] = $thumbnail['path'];
		}

		if(count($possiblethumbnails) === 0) {
			return false;
		}

		if(count($possiblethumbnails) === 1) {
			return current($possiblethumbnails);
		}

		ksort($possiblethumbnails);

		if(key(reset($possiblethumbnails)) > $maxX) {
			return current(reset($possiblethumbnails));
		}

		if(key(end($possiblethumbnails)) < $maxX) {
			return current(end($possiblethumbnails));
		}

		foreach($possiblethumbnails as $width => $path) {
			if($width < $maxX) {
				continue;
			}else{
				return $path;
			}
		}
	}

	/**
	 * @brief return a preview of a file
	 * @return image
	*/
	public function getPreview() {
		if(!is_null($this->preview) && $this->preview->valid()){
			return $this->preview;
		}

		$this->preview = null;
		$file = $this->getFile();
		$maxX = $this->getMaxX();
		$maxY = $this->getMaxY();
		$scalingup = $this->getScalingup();

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];

		$cached = $this->isCached();

		if($cached) {
			$image = new \OC_Image($this->userview->file_get_contents($cached, 'r'));
			$this->preview = $image->valid() ? $image : null;
		}

		if(is_null($this->preview)) {
			$mimetype = $this->fileview->getMimeType($file);
			$preview = null;

			foreach(self::$providers as $supportedmimetype => $provider) {
				if(!preg_match($supportedmimetype, $mimetype)) {
					continue;
				}

				$preview = $provider->getThumbnail($file, $maxX, $maxY, $scalingup, $this->fileview);

				if(!($preview instanceof \OC_Image)) {
					continue;
				}

				$this->preview = $preview;
				$this->resizeAndCrop();

				$previewpath = $this->getThumbnailsFolder() . '/' . $fileid . '/';
				$cachepath = $previewpath . $maxX . '-' . $maxY . '.png';

				if($this->userview->is_dir($this->getThumbnailsFolder() . '/') === false) {
					$this->userview->mkdir($this->getThumbnailsFolder() . '/');
				}

				if($this->userview->is_dir($previewpath) === false) {
					$this->userview->mkdir($previewpath);
				}

				$this->userview->file_put_contents($cachepath, $preview->data());

				break;
			}
		}

		if(is_null($this->preview)) {
			$this->preview = new \OC_Image();
		}

		return $this->preview;
	}

	/**
	 * @brief show preview
	 * @return void
	*/
	public function showPreview() {
		\OCP\Response::enableCaching(3600 * 24); // 24 hours
		if(is_null($this->preview)) {
			$this->getPreview();
		}
		$this->preview->show();
		return;
	}

	/**
	 * @brief show preview
	 * @return void
	*/
	public function show() {
		return $this->showPreview();
	}

	/**
	 * @brief resize, crop and fix orientation
	 * @return image
	*/
	private function resizeAndCrop() {
		$image = $this->preview;
		$x = $this->getMaxX();
		$y = $this->getMaxY();
		$scalingup = $this->getScalingup();
		$maxscalefactor = $this->getMaxScaleFactor();

		if(!($image instanceof \OC_Image)) {
			\OC_Log::write('core', '$this->preview is not an instance of OC_Image', \OC_Log::DEBUG);
			return;
		}

		$image->fixOrientation();

		$realx = (int) $image->width();
		$realy = (int) $image->height();

		if($x === $realx && $y === $realy) {
			$this->preview = $image;
			return true;
		}

		$factorX = $x / $realx;
		$factorY = $y / $realy;

		if($factorX >= $factorY) {
			$factor = $factorX;
		}else{
			$factor = $factorY;
		}

		if($scalingup === false) {
			if($factor > 1) {
				$factor = 1;
			}
		}

		if(!is_null($maxscalefactor)) {
			if($factor > $maxscalefactor) {
				\OC_Log::write('core', 'scalefactor reduced from ' . $factor . ' to ' . $maxscalefactor, \OC_Log::DEBUG);
				$factor = $maxscalefactor;
			}
		}

		$newXsize = (int) ($realx * $factor);
		$newYsize = (int) ($realy * $factor);

		$image->preciseResize($newXsize, $newYsize);

		if($newXsize === $x && $newYsize === $y) {
			$this->preview = $image;
			return;
		}

		if($newXsize >= $x && $newYsize >= $y) {
			$cropX = floor(abs($x - $newXsize) * 0.5);
			//don't crop previews on the Y axis, this sucks if it's a document.
			//$cropY = floor(abs($y - $newYsize) * 0.5);
			$cropY = 0;

			$image->crop($cropX, $cropY, $x, $y);
			
			$this->preview = $image;
			return;
		}

		if($newXsize < $x || $newYsize < $y) {
			if($newXsize > $x) {
				$cropX = floor(($newXsize - $x) * 0.5);
				$image->crop($cropX, 0, $x, $newYsize);
			}

			if($newYsize > $y) {
				$cropY = floor(($newYsize - $y) * 0.5);
				$image->crop(0, $cropY, $newXsize, $y);
			}

			$newXsize = (int) $image->width();
			$newYsize = (int) $image->height();

			//create transparent background layer
			$backgroundlayer = imagecreatetruecolor($x, $y);
			$white = imagecolorallocate($backgroundlayer, 255, 255, 255);
			imagefill($backgroundlayer, 0, 0, $white);

			$image = $image->resource();

			$mergeX = floor(abs($x - $newXsize) * 0.5);
			$mergeY = floor(abs($y - $newYsize) * 0.5);

			imagecopy($backgroundlayer, $image, $mergeX, $mergeY, 0, 0, $newXsize, $newYsize);

			//$black = imagecolorallocate(0,0,0);
			//imagecolortransparent($transparentlayer, $black);

			$image = new \OC_Image($backgroundlayer);

			$this->preview = $image;
			return;
		}
	}

	/**
	 * @brief register a new preview provider to be used
	 * @param string $provider class name of a Preview_Provider
	 * @return void
	 */
	public static function registerProvider($class, $options=array()) {
		self::$registeredProviders[]=array('class'=>$class, 'options'=>$options);
	}

	/**
	 * @brief create instances of all the registered preview providers
	 * @return void
	 */
	private static function initProviders() {
		if(count(self::$providers)>0) {
			return;
		}

		foreach(self::$registeredProviders as $provider) {
			$class=$provider['class'];
			$options=$provider['options'];

			$object = new $class($options);

			self::$providers[$object->getMimeType()] = $object;
		}

		$keys = array_map('strlen', array_keys(self::$providers));
		array_multisort($keys, SORT_DESC, self::$providers);
	}

	/**
	 * @brief method that handles preview requests from users that are logged in
	 * @return void
	*/
	public static function previewRouter() {
		\OC_Util::checkLoggedIn();

		$file = array_key_exists('file', $_GET) ? (string) urldecode($_GET['file']) : '';
		$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : '44';
		$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : '44';
		$scalingup = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;

		if($file === '') {
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'No file parameter was passed', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		if($maxX === 0 || $maxY === 0) {
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		try{
			$preview = new Preview(\OC_User::getUser(), 'files');
			$preview->setFile($file);
			$preview->setMaxX($maxX);
			$preview->setMaxY($maxY);
			$preview->setScalingUp($scalingup);

			$preview->show();
		}catch(\Exception $e) {
			\OC_Response::setStatus(500);
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			self::showErrorPreview();
			exit;
		}
	}

	/**
	 * @brief method that handles preview requests from users that are not logged in / view shared folders that are public
	 * @return void
	*/
	public static function publicPreviewRouter() {
		if(!\OC_App::isEnabled('files_sharing')){
			exit;
		}

		$file = array_key_exists('file', $_GET) ? (string) urldecode($_GET['file']) : '';
		$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : '44';
		$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : '44';
		$scalingup = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;
		$token = array_key_exists('t', $_GET) ? (string) $_GET['t'] : '';

		if($token === ''){
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'No token parameter was passed', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		$linkedItem = \OCP\Share::getShareByToken($token);
		if($linkedItem === false || ($linkedItem['item_type'] !== 'file' && $linkedItem['item_type'] !== 'folder')) {
			\OC_Response::setStatus(404);
			\OC_Log::write('core-preview', 'Passed token parameter is not valid', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		if(!isset($linkedItem['uid_owner']) || !isset($linkedItem['file_source'])) {
			\OC_Response::setStatus(500);
			\OC_Log::write('core-preview', 'Passed token seems to be valid, but it does not contain all necessary information . ("' . $token . '")');
			self::showErrorPreview();
			exit;
		}

		$userid = $linkedItem['uid_owner'];
		\OC_Util::setupFS($userid);

		$pathid = $linkedItem['file_source'];
		$path = \OC\Files\Filesystem::getPath($pathid);
		$pathinfo = \OC\Files\Filesystem::getFileInfo($path);
		$sharedfile = null;

		if($linkedItem['item_type'] === 'folder') {
			$isvalid = \OC\File\Filesystem::isValidPath($file);
			if(!$isvalid) {
				\OC_Response::setStatus(400); //400 Bad Request
				\OC_Log::write('core-preview', 'Passed filename is not valid, might be malicious (file:"' . $file . '";ip:"' . $_SERVER['REMOTE_ADDR'] . '")', \OC_Log::WARN);
				self::showErrorPreview();
				exit;
			}
			$sharedfile = \OC\Files\Filesystem::normalizePath($file);
		}

		if($linkedItem['item_type'] === 'file') {
			$parent = $pathinfo['parent'];
			$path = \OC\Files\Filesystem::getPath($parent);
			$sharedfile = $pathinfo['name'];
		}

		$path = \OC\Files\Filesystem::normalizePath($path, false);
		if(substr($path, 0, 1) == '/') {
			$path = substr($path, 1);
		}

		if($maxX === 0 || $maxY === 0) {
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		$root = 'files/' . $path;

		try{
			$preview = new Preview($userid, $path);
			$preview->setFile($file);
			$preview->setMaxX($maxX);
			$preview->setMaxY($maxY);
			$preview->setScalingUp($scalingup);

			$preview->show();
		}catch(\Exception $e) {
			\OC_Response::setStatus(500);
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			self::showErrorPreview();
			exit;
		}
	}

	public static function trashbinPreviewRouter() {
		\OC_Util::checkLoggedIn();

		if(!\OC_App::isEnabled('files_trashbin')){
			exit;
		}

		$file = array_key_exists('file', $_GET) ? (string) urldecode($_GET['file']) : '';
		$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : '44';
		$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : '44';
		$scalingup = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;

		if($file === '') {
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'No file parameter was passed', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		if($maxX === 0 || $maxY === 0) {
			\OC_Response::setStatus(400); //400 Bad Request
			\OC_Log::write('core-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
			self::showErrorPreview();
			exit;
		}

		try{
			$preview = new Preview(\OC_User::getUser(), 'files_trashbin/files');
			$preview->setFile($file);
			$preview->setMaxX($maxX);
			$preview->setMaxY($maxY);
			$preview->setScalingUp($scalingup);

			$preview->showPreview();
		}catch(\Exception $e) {
			\OC_Response::setStatus(500);
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			self::showErrorPreview();
			exit;
		}
	}

	public static function post_write($args) {
		self::post_delete($args);
	}
	
	public static function post_delete($args) {
		$path = $args['path'];
		if(substr($path, 0, 1) == '/') {
			$path = substr($path, 1);
		}
		$preview = new Preview(\OC_User::getUser(), 'files/', $path, 0, 0, false, true);
		$preview->deleteAllPreviews();
	}
	
	private static function showErrorPreview() {
		$path = \OC::$SERVERROOT . '/core/img/actions/delete.png';
		$preview = new \OC_Image($path);
		$preview->preciseResize(44, 44);
		$preview->show();
	}
}