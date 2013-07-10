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
	//the thumbnail  folder
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
	 * @param $user userid
	 * @param $root path of root
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @return mixed (bool / string) 
	 *					false if thumbnail does not exist
	 *					path to thumbnail if thumbnail exists
	*/
	public function __construct($user=null, $root='', $file='', $maxX=0, $maxY=0, $scalingup=true, $force=false) {
		//set config
		$this->max_x = \OC_Config::getValue('preview_max_x', null);
		$this->max_y = \OC_Config::getValue('preview_max_y', null);
		$this->max_scale_factor = \OC_Config::getValue('preview_max_scale_factor', 10);

		//save parameters
		$this->file = $file;
		$this->maxX = $maxX;
		$this->maxY = $maxY;
		$this->scalingup = $scalingup;

		//init fileviews
		$this->fileview = new \OC\Files\View('/' . $user . '/' . $root);
		$this->userview = new \OC\Files\View('/' . $user);

		if($force !== true) {
			if(!is_null($this->max_x)) {
				if($this->maxX > $this->max_x) {
					\OC_Log::write('core', 'maxX reduced from ' . $this->maxX . ' to ' . $this->max_x, \OC_Log::DEBUG);
					$this->maxX = $this->max_x;
				}
			}

			if(!is_null($this->max_y)) {
				if($this->maxY > $this->max_y) {
					\OC_Log::write('core', 'maxY reduced from ' . $this->maxY . ' to ' . $this->max_y, \OC_Log::DEBUG);
					$this->maxY = $this->max_y;
				}
			}

			$fileinfo = $this->fileview->getFileInfo($this->file);
			if(array_key_exists('size', $fileinfo)){
				if((int) $fileinfo['size'] === 0){
					\OC_Log::write('core', 'You can\'t generate a preview of a 0 byte file (' . $this->file . ')', \OC_Log::ERROR);
					throw new \Exception('0 byte file given');	
				}
			}

			//init providers
			if(empty(self::$providers)) {
				self::initProviders();
			}

			//check if there are any providers at all
			if(empty(self::$providers)) {
				\OC_Log::write('core', 'No preview providers exist', \OC_Log::ERROR);
				throw new \Exception('No providers');
			}

			//validate parameters
			if($file === '') {
				\OC_Log::write('core', 'No filename passed', \OC_Log::ERROR);
				throw new \Exception('File not found');
			}

			//check if file exists
			if(!$this->fileview->file_exists($file)) {
				\OC_Log::write('core', 'File:"' . $file . '" not found', \OC_Log::ERROR);
				throw new \Exception('File not found');
			}

			//check if given size makes sense
			if($maxX === 0 || $maxY === 0) {
				\OC_Log::write('core', 'Can not create preview with 0px width or 0px height', \OC_Log::ERROR);
				throw new \Exception('Height and/or width set to 0');
			}
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
	public function getThumbnailsfolder() {
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
	 * @brief deletes previews of a file with specific x and y
	 * @return bool
	*/
	public function deletePreview() {
		$fileinfo = $this->fileview->getFileInfo($this->file);
		$fileid = $fileinfo['fileid'];

		$this->userview->unlink(self::THUMBNAILS_FOLDER . '/' . $fileid . '/' . $this->maxX . '-' . $this->maxY . '.png');
		return;
	}

	/**
	 * @brief deletes all previews of a file
	 * @return bool
	*/
	public function deleteAllPreviews() {
		$fileinfo = $this->fileview->getFileInfo($this->file);
		$fileid = $fileinfo['fileid'];

		$this->userview->deleteAll(self::THUMBNAILS_FOLDER . '/' . $fileid . '/');
		$this->userview->rmdir(self::THUMBNAILS_FOLDER . '/' . $fileid . '/');
		return;
	}

	/**
	 * @brief check if thumbnail or bigger version of thumbnail of file is cached
	 * @return mixed (bool / string) 
	 *				false if thumbnail does not exist
	 *				path to thumbnail if thumbnail exists
	*/
	private function isCached() {
		$file = $this->file;
		$maxX = $this->maxX;
		$maxY = $this->maxY;
		$scalingup = $this->scalingup;

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];

		if(!$this->userview->is_dir(self::THUMBNAILS_FOLDER . '/' . $fileid)) {
			return false;
		}

		//does a preview with the wanted height and width already exist?
		if($this->userview->file_exists(self::THUMBNAILS_FOLDER . '/' . $fileid . '/' . $maxX . '-' . $maxY . '.png')) {
			return self::THUMBNAILS_FOLDER . '/' . $fileid . '/' . $maxX . '-' . $maxY . '.png';
		}

		$wantedaspectratio = $maxX / $maxY;

		//array for usable cached thumbnails
		$possiblethumbnails = array();

		$allthumbnails = $this->userview->getDirectoryContent(self::THUMBNAILS_FOLDER . '/' . $fileid);
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
					if($scalefactor > $this->max_scale_factor) {
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
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return image
	*/
	public function getPreview() {
		$file = $this->file;
		$maxX = $this->maxX;
		$maxY = $this->maxY;
		$scalingup = $this->scalingup;

		$fileinfo = $this->fileview->getFileInfo($file);
		$fileid = $fileinfo['fileid'];

		$cached = self::isCached();

		if($cached) {
			$image = new \OC_Image($this->userview->file_get_contents($cached, 'r'));
			$this->preview = $image;
		}else{
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

				//are there any cached thumbnails yet
				if($this->userview->is_dir(self::THUMBNAILS_FOLDER . '/') === false) {
					$this->userview->mkdir(self::THUMBNAILS_FOLDER . '/');
				}

				//cache thumbnail
				$cachepath = self::THUMBNAILS_FOLDER . '/' . $fileid . '/' . $maxX . '-' . $maxY . '.png';
				if($this->userview->is_dir(self::THUMBNAILS_FOLDER . '/' . $fileid . '/') === false) {
					$this->userview->mkdir(self::THUMBNAILS_FOLDER . '/' . $fileid . '/');
				}

				$preview->fixOrientation();
				$this->userview->file_put_contents($cachepath, $preview->data());

				break;
			}

			if(is_null($preview) || $preview === false) {
				$preview = new \OC_Image();
			}

			$this->preview = $preview;
		}
		$this->resizeAndCrop();
		return $this->preview;
	}

	/**
	 * @brief return a preview of a file
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return void
	*/
	public function showPreview() {
		\OCP\Response::enableCaching(3600 * 24); // 24 hour
		$preview = $this->getPreview();
		if($preview) {
			$preview->show();
		}
	}

	/**
	 * @brief resize, crop and fix orientation
	 * @return image
	*/
	public function resizeAndCrop() {
		$image = $this->preview;
		$x = $this->maxX;
		$y = $this->maxY;
		$scalingup = $this->scalingup;

		if(!($image instanceof \OC_Image)) {
			\OC_Log::write('core', 'Object passed to resizeAndCrop is not an instance of OC_Image', \OC_Log::DEBUG);
			return;
		}

		$realx = (int) $image->width();
		$realy = (int) $image->height();

		if($x === $realx && $y === $realy) {
			return $image;
		}

		$factorX = $x / $realx;
		$factorY = $y / $realy;
		
		if($factorX >= $factorY) {
			$factor = $factorX;
		}else{
			$factor = $factorY;
		}
		
		// only scale up if requested
		if($scalingup === false) {
			if($factor>1) $factor=1;
		}
		if(!is_null($this->max_scale_factor)) {
			if($factor > $this->max_scale_factor) {
				\OC_Log::write('core', 'scalefactor reduced from ' . $factor . ' to ' . $this->max_scale_factor, \OC_Log::DEBUG);
				$factor = $this->max_scale_factor;
			}
		}
		$newXsize = $realx * $factor;
		$newYsize = $realy * $factor;

		// resize
		$image->preciseResize($newXsize, $newYsize);

		if($newXsize === $x && $newYsize === $y) {
			$this->preview = $image;
			return;
		}

		if($newXsize >= $x && $newYsize >= $y) {
			$cropX = floor(abs($x - $newXsize) * 0.5);
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
	public static function previewRouter($params) {
		\OC_Util::checkLoggedIn();

		$file = '';
		$maxX = 0;
		$maxY = 0;
		$scalingup = true;

		if(array_key_exists('file', $_GET)) $file = (string) urldecode($_GET['file']);
		if(array_key_exists('x', $_GET)) $maxX = (int) $_GET['x'];
		if(array_key_exists('y', $_GET)) $maxY = (int) $_GET['y'];
		if(array_key_exists('scalingup', $_GET)) $scalingup = (bool) $_GET['scalingup'];

		if($file !== '' && $maxX !== 0 && $maxY !== 0) {
			try{
				$preview = new Preview(\OC_User::getUser(), 'files', $file,  $maxX, $maxY, $scalingup);
				$preview->showPreview();
			}catch(\Exception $e) {
				\OC_Response::setStatus(404);
				\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
				exit;
			}
		}else{
			\OC_Response::setStatus(404);
			exit;
		}
	}

	/**
	 * @brief method that handles preview requests from users that are not logged in / view shared folders that are public
	 * @return void
	*/
	public static function publicPreviewRouter($params) {
		$file = '';
		$maxX = 0;
		$maxY = 0;
		$scalingup = true;
		$token = '';

		$user = null;
		$path = null;

		if(array_key_exists('file', $_GET)) $file = (string) urldecode($_GET['file']);
		if(array_key_exists('x', $_GET)) $maxX = (int) $_GET['x'];
		if(array_key_exists('y', $_GET)) $maxY = (int) $_GET['y'];
		if(array_key_exists('scalingup', $_GET)) $scalingup = (bool) $_GET['scalingup'];
		if(array_key_exists('t', $_GET)) $token = (string) $_GET['t'];

		$linkItem = \OCP\Share::getShareByToken($token);

		if (is_array($linkItem) && isset($linkItem['uid_owner']) && isset($linkItem['file_source'])) {
			$userid = $linkItem['uid_owner'];
			\OC_Util::setupFS($userid);

			$pathid = $linkItem['file_source'];
			$path = \OC\Files\Filesystem::getPath($pathid);
			$pathinfo = \OC\Files\Filesystem::getFileInfo($path);

			$sharedfile = null;
			if($linkItem['item_type'] === 'folder') {
				//clean up file parameter
				$sharedfile = \OC\Files\Filesystem::normalizePath($file);
				if(!\OC\Files\Filesystem::isValidPath($file)) {
					\OC_Response::setStatus(403);
					exit;
				}
			} else if($linkItem['item_type'] === 'file') {
				$parent = $pathinfo['parent'];
				$path = \OC\Files\Filesystem::getPath($parent);
				$sharedfile = $pathinfo['name'];
			}

			$path = \OC\Files\Filesystem::normalizePath($path, false);
			if(substr($path, 0, 1) == '/') {
				$path = substr($path, 1);
			}
		}

		if($userid !== null && $path !== null && $sharedfile !== null) {
			try{
				$preview = new Preview($userid, 'files/' . $path, $sharedfile, $maxX, $maxY, $scalingup);
				$preview->showPreview();
			}catch(\Exception $e) {
				\OC_Response::setStatus(404);
				\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
				exit;
			}
		}else{
			\OC_Response::setStatus(404);
			exit;
		}
	}

	public static function trashbinPreviewRouter() {
		if(!\OC_App::isEnabled('files_trashbin')){
			exit;
		}
		\OC_Util::checkLoggedIn();

		$file = '';
		$maxX = 0;
		$maxY = 0;
		$scalingup = true;

		if(array_key_exists('file', $_GET)) $file = (string) urldecode($_GET['file']);
		if(array_key_exists('x', $_GET)) $maxX = (int) $_GET['x'];
		if(array_key_exists('y', $_GET)) $maxY = (int) $_GET['y'];
		if(array_key_exists('scalingup', $_GET)) $scalingup = (bool) $_GET['scalingup'];

		if($file !== '' && $maxX !== 0 && $maxY !== 0) {
			try{
				$preview = new Preview(\OC_User::getUser(), 'files_trashbin/files', $file, $maxX, $maxY, $scalingup);
				$preview->showPreview();
			}catch(\Exception $e) {
				\OC_Response::setStatus(404);
				\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
				exit;
			}
		}else{
			\OC_Response::setStatus(404);
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
}