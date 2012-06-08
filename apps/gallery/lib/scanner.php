<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski <bartek@alefzero.eu>
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Lesser General Public 
* License along with this library. If not, see <http://www.gnu.org/licenses/>.
* 
*/

class OC_Gallery_Scanner {

	public static function getGalleryRoot() {
		return OCP\Config::getUserValue(OCP\USER::getUser(), 'gallery', 'root', '/');
	}
	public static function getScanningRoot() {
		return OC_Filesystem::getRoot().self::getGalleryRoot();
	}

	public static function cleanUp() {
		OC_Gallery_Album::cleanup();
	}

	public static function createName($name) {
		$name = basename($name);
		return $name == '.' ? '' : $name;
	}

	// Scan single dir relative to gallery root
	public static function scan($eventSource) {
		$paths = self::findPaths();
		$eventSource->send('count', count($paths)+1);
		$owner = OCP\USER::getUser();
		foreach ($paths as $path) {
			$name = self::createName($path);
			$images = self::findFiles($path);

			$result = OC_Gallery_Album::find($owner, null, $path);
			// don't duplicate galleries with same path
			if (!($albumId = $result->fetchRow())) {
				OC_Gallery_Album::create($owner, $name, $path);
				$result = OC_Gallery_Album::find($owner, $name, $path);
				$albumId = $result->fetchRow();
			}
			$albumId = $albumId['album_id'];
			foreach ($images as $img) {
				$result = OC_Gallery_Photo::find($albumId, $img);
				if (!$result->fetchRow())
					OC_Gallery_Photo::create($albumId, $img);
			}
			if (count($images))
				self::createThumbnails($name, $images);
			$eventSource->send('scanned', '');
		}
		self::createIntermediateAlbums();
		$eventSource->send('scanned', '');
		$eventSource->send('done', 1);
	}

	public static function createThumbnails($albumName, $files) {
		// create gallery thumbnail
		$file_count = min(count($files), 10);
		$thumbnail = imagecreatetruecolor($file_count*200, 200);
		for ($i = 0; $i < $file_count; $i++) {
			$image = OC_Gallery_Photo::getThumbnail($files[$i]);
			if ($image && $image->valid()) {
				imagecopyresampled($thumbnail, $image->resource(), $i*200, 0, 0, 0, 200, 200, 200, 200);
				$image->destroy();
			}
		}
		imagepng($thumbnail, OCP\Config::getSystemValue("datadirectory").'/'. OCP\USER::getUser() .'/gallery/' . $albumName.'.png');
		imagedestroy($thumbnail);
	}

	public static function createIntermediateAlbums() {
		$paths = self::findPaths();
		for ($i = 1; $i < count($paths); $i++) {
			$prevLen = strlen($paths[$i-1]);
			if (strncmp($paths[$i-1], $paths[$i], $prevLen)==0) {
				$s = substr($paths[$i], $prevLen);
				if (strrpos($s, '/') != 0) {
					$a = explode('/', trim($s, '/'));
					$p = $paths[$i-1];
					foreach ($a as $e) {
						$p .= ($p == '/'?'':'/').$e;
						OC_Gallery_Album::create(OCP\USER::getUser(), $e, $p);
						$arr = OC_FileCache::searchByMime('image','', OC_Filesystem::getRoot().$p);
						$step = floor(count($arr)/10);
						if ($step == 0) $step = 1;
						$na = array();
						for ($j = 0; $j < count($arr); $j+=$step) {
							$na[] = $p.$arr[$j];
						}
						if (count($na))
							self::createThumbnails($e, $na);
					}
				}
			} 
		}
	}

	public static function isPhoto($filename) {
		$ext = strtolower(substr($filename, strrpos($filename, '.')+1));
		return $ext=='png' || $ext=='jpeg' || $ext=='jpg' || $ext=='gif';
	}

	public static function findFiles($path) {
		$images = OC_FileCache::searchByMime('image','', OC_Filesystem::getRoot().$path);
		$new = array();
		foreach ($images as $i)
			if (strpos($i, '/',1) === FALSE)
				$new[] = $path.$i;
		return $new;
	}

	public static function findPaths() {
		$images=OC_FileCache::searchByMime('image','', self::getScanningRoot());
		$paths=array();
		foreach($images as $image){
			$path=dirname($image);
			$path = self::getGalleryRoot().($path=='.'?'':$path);
			if ($path !== '/') $path=rtrim($path,'/');
			if(array_search($path,$paths)===false){
				$paths[]=$path;
			}
			// add sub path also if they don't contain images
			while ( ($path = dirname($path)) != '/') {
				if(array_search($path,$paths)===false){
					$paths[]=$path;
				}
			}
		}
		sort($paths);
		return $paths;
	}
}
