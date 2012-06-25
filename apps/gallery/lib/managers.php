<?php

namespace OC\Pictures;

require_once('lib/base.php');

class DatabaseManager {
	private static $instance = null;
	const TAG = 'DatabaseManager';
	
	public static function getInstance() {
		if (self::$instance === null)
			self::$instance = new DatabaseManager();
		return self::$instance;
	}
	
	public function getFileData($path) {
		$gallery_path = \OCP\Config::getSystemValue( 'datadirectory' ).'/'.\OC_User::getUser().'/gallery';
		$path = $gallery_path.$path;
		$stmt = \OCP\DB::prepare('SELECT * FROM *PREFIX*pictures_images_cache WHERE uid_owner LIKE ? AND path = ?');
		$result = $stmt->execute(array(\OCP\USER::getUser(), $path));
		if (($row = $result->fetchRow()) != false) {
			return $row;
		}
		$image = new \OC_Image();
		if (!$image->loadFromFile($path)) {
			return false;
		}
		$stmt = \OCP\DB::prepare('INSERT INTO *PREFIX*pictures_images_cache (uid_owner, path, width, height) VALUES (?, ?, ?, ?)');
		$stmt->execute(array(\OCP\USER::getUser(), $path, $image->width(), $image->height()));
		$ret = array('path' => $path, 'width' => $image->width(), 'height' => $image->height());
		unset($image);
		return $ret;
	}
	
	private function __construct() {}
}

class ThumbnailsManager {
	
	private static $instance = null;
	const TAG = 'ThumbnailManager';
	
	public static function getInstance() {
		if (self::$instance === null)
			self::$instance = new ThumbnailsManager();
		return self::$instance;
	}

	public function getThumbnail($path) {
		$gallery_path = \OCP\Config::getSystemValue( 'datadirectory' ).'/'.\OC_User::getUser().'/gallery';
		if (file_exists($gallery_path.$path)) {
			return new \OC_Image($gallery_path.$path);
		}
		if (!\OC_Filesystem::file_exists($path)) {
			\OC_Log::write(self::TAG, 'File '.$path.' don\'t exists', \OC_Log::WARN);
			return false;
		}
		$image = new \OC_Image();
		$image->loadFromFile(\OC_Filesystem::getLocalFile($path));
		if (!$image->valid()) return false;

		$image->fixOrientation();

		$ret = $image->preciseResize(floor((150*$image->width())/$image->height()), 150);
		
		if (!$ret) {
			\OC_Log::write(self::TAG, 'Couldn\'t resize image', \OC_Log::ERROR);
			unset($image);
			return false;
		}

		$image->save($gallery_path.'/'.$path);
		return $image;
	}
	
	public function getThumbnailInfo($path) {
		$arr = DatabaseManager::getInstance()->getFileData($path);
		if (!$arr) {
			$thubnail = $this->getThumbnail($path);
			unset($thubnail);
			$arr = DatabaseManager::getInstance()->getFileData($path);
		}
		$ret = array('filepath' => $arr['path'],
					 'width' => $arr['width'],
					 'height' => $arr['height']);
		return $ret;
	}
	
	public function delete($path) {
		$thumbnail = \OCP\Config::getSystemValue('datadirectory').'/'.\OC_User::getUser()."/gallery".$path;
		if (file_exists($thumbnail)) {
			unlink($thumbnail);
		}
	}
	
	private function __construct() {}

}
?>
