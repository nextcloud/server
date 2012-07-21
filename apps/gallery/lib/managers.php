<?php

namespace OC\Pictures;

class DatabaseManager {        
	private static $instance = null;
	protected $cache = array();
	const TAG = 'DatabaseManager';
	
	public static function getInstance() {
		if (self::$instance === null)
			self::$instance = new DatabaseManager();
		return self::$instance;
	}
	
	protected function getPathData($path) {
		$stmt = \OCP\DB::prepare('SELECT * FROM *PREFIX*pictures_images_cache
			WHERE uid_owner LIKE ? AND path like ? AND path not like ?');
		$path_match = $path.'/%';
		$path_notmatch = $path.'/%/%';
		$result = $stmt->execute(array(\OCP\USER::getUser(), $path_match, $path_notmatch));
		$this->cache[$path] = array();
		while (($row = $result->fetchRow()) != false) {
			$this->cache[$path][$row['path']] = $row;
		}
	}

	public function setFileData($path, $width, $height) {
		$stmt = \OCP\DB::prepare('INSERT INTO *PREFIX*pictures_images_cache (uid_owner, path, width, height) VALUES (?, ?, ?, ?)');
		$stmt->execute(array(\OCP\USER::getUser(), $path, $width, $height));
		$ret = array('path' => $path, 'width' => $width, 'height' => $height);
		$dir = dirname($path);
		$this->cache[$dir][$path] = $ret;
		return $ret;
	}

	public function getFileData($path) {
		$gallery_path = \OCP\Config::getSystemValue( 'datadirectory' ).'/'.\OC_User::getUser().'/gallery';
		$path = $gallery_path.$path;
		$dir = dirname($path);
		if (!isset($this->cache[$dir])) {
			$this->getPathData($dir);
		}
		if (isset($this->cache[$dir][$path])) {
			return $this->cache[$dir][$path];
		}
		$image = new \OC_Image();
		if (!$image->loadFromFile($path)) {
			return false;
		}
		$ret = $this->setFileData($path, $image->width(), $image->height());
		unset($image);
		$this->cache[$dir][$path] = $ret;
		return $ret;
	}
	
	private function __construct() {}
}

class ThumbnailsManager {
	
	private static $instance = null;
	const TAG = 'ThumbnailManager';
        const THUMBNAIL_HEIGHT = 150;
	
	public static function getInstance() {
		if (self::$instance === null)
			self::$instance = new ThumbnailsManager();
		return self::$instance;
	}

	public function getThumbnail($path) {
		$gallery_storage = \OCP\Files::getStorage('gallery');
		if ($gallery_storage->file_exists($path)) {
			return new \OC_Image($gallery_storage->getLocalFile($path));
		}
		if (!\OC_Filesystem::file_exists($path)) {
			\OC_Log::write(self::TAG, 'File '.$path.' don\'t exists', \OC_Log::WARN);
			return false;
		}
		$image = new \OC_Image();
		$image->loadFromFile(\OC_Filesystem::getLocalFile($path));
		if (!$image->valid()) return false;
                            
		$image->fixOrientation();
                
		$ret = $image->preciseResize( floor((self::THUMBNAIL_HEIGHT*$image->width())/$image->height()), self::THUMBNAIL_HEIGHT );
		
		if (!$ret) {
			\OC_Log::write(self::TAG, 'Couldn\'t resize image', \OC_Log::ERROR);
			unset($image);
			return false;
		}
		$l = $gallery_storage->getLocalFile($path);
                
		$image->save($l);
		return $image;
	}

	public function getThumbnailWidth($image) {
		return floor((self::THUMBNAIL_HEIGHT*$image->widthTopLeft())/$image->heightTopLeft());
	}

	public function getThumbnailInfo($path) {
		$arr = DatabaseManager::getInstance()->getFileData($path);
		if (!$arr) {
			if (!\OC_Filesystem::file_exists($path)) {
				\OC_Log::write(self::TAG, 'File '.$path.' don\'t exists', \OC_Log::WARN);
				return false;
			}
			$image = new \OC_Image();
			$image->loadFromFile(\OC_Filesystem::getLocalFile($path));
			if (!$image->valid()) {
				return false;
			}
			$arr = DatabaseManager::getInstance()->setFileData($path, $this->getThumbnailWidth($image), self::THUMBNAIL_HEIGHT);
		}
		$ret = array('filepath' => $arr['path'],
					 'width' => $arr['width'],
					 'height' => $arr['height']);
		return $ret;
	}
	
	public function delete($path) {
		$thumbnail_storage = \OCP\Files::getStorage('gallery');
		if ($thumbnail_storage->file_exists($path)) {
			$thumbnail_storage->unlink($path);
		}
	}
	
	private function __construct() {}

}
