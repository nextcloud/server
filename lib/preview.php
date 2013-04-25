<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
/*
 * Thumbnails:
 * structure of filename:
 * /data/user/thumbnails/pathhash/x-y.png
 * 
 */

class OC_Preview {
	//the thumbnail  folder
	const THUMBNAILS_FOLDER = 'thumbnails';
	const MAX_SCALE_FACTOR = 2;

	//fileview object
	static private $fileview = null;

	//preview providers
	static private $providers = array();
	static private $registeredProviders = array();

	/**
	 * @brief check if thumbnail or bigger version of thumbnail of file is cached
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @return mixed (bool / string) 
	 *					false if thumbnail does not exist
	 *					path to thumbnail if thumbnail exists
	*/
	private static function isCached($file, $maxX, $maxY, $scalingup){
		$fileinfo = self::$fileview->getFileInfo($file);
		$fileid = self::$fileinfo['fileid'];
		
		//echo self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid;
		if(!self::$fileview->is_dir(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid)){
			return false;
		}
		
		//does a preview with the wanted height and width already exist?
		if(self::$fileview->file_exists(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid . PATH_SEPARATOR . $x . '-' . $y . '.png')){
			return self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid . PATH_SEPARATOR . $x . '-' . $y . '.png';
		}
		
		$wantedaspectratio = $maxX / $maxY;
		
		//array for usable cached thumbnails
		$possiblethumbnails = array();
		
		$allthumbnails = self::$fileview->getDirectoryContent(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid);
		foreach($allthumbnails as $thumbnail){
			$size = explode('-', $thumbnail['name']);
			$x = $size[0];
			$y = $size[1];
			
			$aspectratio = $x / $y;
			if($aspectratio != $wantedaspectratio){
				continue;
			}
			
			if($x < $maxX || $y < $maxY){
				if($scalingup){
					$scalefactor = $maxX / $x;
					if($scalefactor > self::MAX_SCALE_FACTOR){
						continue;
					}
				}else{
					continue;
				}
			}
			
			$possiblethumbnails[$x] = $thumbnail['path'];
		}
		
		if(count($possiblethumbnails) === 0){
			return false;
		}
		
		if(count($possiblethumbnails) === 1){
			return current($possiblethumbnails);
		}
		
		ksort($possiblethumbnails);
		
		if(key(reset($possiblethumbnails)) > $maxX){
			return current(reset($possiblethumbnails));
		}
		
		if(key(end($possiblethumbnails)) < $maxX){
			return current(end($possiblethumbnails));
		}
		
		foreach($possiblethumbnails as $width => $path){
			if($width < $maxX){
				continue;
			}else{
				return $path;
			}
		}
	}

	/**
	 * @brief delete a preview with a specfic height and width
	 * @param $file path to the file
	 * @param $x width of preview
	 * @param $y height of preview
	 * @return image
	*/
	public static function deletePreview($file, $x, $y){
		self::init();
		
		$fileinfo = self::$fileview->getFileInfo($file);
		$fileid = self::$fileinfo['fileid'];
		
		return self::$fileview->unlink(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid . PATH_SEPARATOR . $x . '-' . $y . '.png');
	}

	/**
	 * @brief deletes all previews of a file
	 * @param $file path of file
	 * @return bool
	*/
	public static function deleteAllPrevies($file){
		self::init();
		
		$fileinfo = self::$fileview->getFileInfo($file);
		$fileid = self::$fileinfo['fileid'];
		
		return self::$fielview->rmdir(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid);
	}

	/**
	 * @brief return a preview of a file
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return image
	*/
	public static function getPreview($file, $maxX, $maxY, $scalingup){
		self::init();
		
		$cached = self::isCached($file, $maxX, $maxY);
		if($cached){
			$image = new \OC_Image($cached);
			if($image->width() != $maxX && $image->height != $maxY){
				$image->preciseResize($maxX, $maxY);
			}
			return $image;
		}
		
		$mimetype = self::$fileview->getMimeType($file);
		
		$preview;
		
		foreach(self::$providers as $supportedmimetype => $provider){
			if(!preg_match($supportedmimetype, $mimetype)){
				continue;
			}
			
			$preview = $provider->getThumbnail($file, $maxX, $maxY, $scalingup);
			
			if(!$preview){
				continue;
			}
			
			if(!($preview instanceof \OC_Image)){
				$preview = @new \OC_Image($preview);
			}
			
			//cache thumbnail
			$preview->save(self::$filesview->getAbsolutePath(self::THUMBNAILS_FOLDER . PATH_SEPARATOR . $fileid . PATH_SEPARATOR . $x . '-' . $y . '.png'));
			
			break;
		}
		
		return $preview;
	}

	/**
	 * @brief return a preview of a file
	 * @param $file The path to the file where you want a thumbnail from
	 * @param $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param $scaleup Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return image
	*/
	public static function showPreview($file, $maxX, $maxY, $scalingup = true, $fontsize = 12){
		OCP\Response::enableCaching(3600 * 24); // 24 hour
		$preview = self::getPreview($file, $maxX, $maxY, $scalingup, $fontsize);
		$preview->show();
	}
	
	/**
	 * @brief check whether or not providers and views are initialized and initialize if not
	 * @return void
	*/
	private static function init(){
		if(empty(self::$providers)){
			self::initProviders();
		}
		if(is_null(self::$fileview)){
			self::initViews();
		}
	}

	/**
	 * @brief register a new preview provider to be used
	 * @param string $provider class name of a OC_Preview_Provider
	 * @return void
	 */
	public static function registerProvider($class, $options=array()){
		self::$registeredProviders[]=array('class'=>$class, 'options'=>$options);
	}

	/**
	 * @brief create instances of all the registered preview providers
	 * @return void
	 */
	private static function initProviders(){
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
	 * @brief initialize a new \OC\Files\View object
	 * @return void
	*/
	private static function initViews(){
		if(is_null(self::$fileview)){
			//does this work with LDAP?
			self::$fileview = new OC\Files\View(OC_User::getUser());
		}
	}
	
	public static function previewRouter($params){
		self::init();
		
		$file = (string) urldecode($_GET['file']);
		$maxX = (int) $_GET['x'];
		$maxY = (int) $_GET['y'];
		$scalingup = (bool) $_GET['scalingup'];
		
		$path = 'files/' . $file;
		
		if($maxX === 0 || $maxY === 0){
			OC_Log::write('core', 'Can not create preview with 0px width or 0px height', OC_Log::DEBUG);
			exit;
		}
		
		var_dump(self::$fileview->file_exists($path));
		var_dump(self::$fileview->getDirectoryContent());
		var_dump(self::$fileview->getDirectoryContent('files/'));
		var_dump($path);
		var_dump(self::$fileview->filesize($path));
		var_dump(self::$fileview->getAbsolutePath('/'));
		
		if(!self::$fileview->filesize($path)){
			OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
		}
		
		self::showPreview($file, $maxX, $maxY, $scalingup);
	}
}