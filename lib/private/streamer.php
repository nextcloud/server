<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

use \ZipStreamer\ZipStreamer;
use DeepDiver1975\TarStreamer\TarStreamer;

class OC_Streamer {

	private $streamerInstance;
	private $extension;
	
	public function __construct(){
		if (0) {
			$this->streamerInstance = new ZipStreamer();
		} else {
			$this->streamerInstance = new TarStreamer();
		}
	}
	
	public function sendHeaders($name){
		$extension = $this->streamerInstance instanceof ZipStreamer ? '.zip' : '.tar';
		return $this->streamerInstance->sendHeaders($name . $extension);
	}
	
	/**
	 * @param string $dir
	 * @param string $internalDir
	 */
	public function addDirRecoursive($dir, $internalDir='') {
		$dirname = basename($dir);
		$rootDir = $internalDir . $dirname;
		if (!empty($rootDir)) {
			$this->streamerInstance->addEmptyDir($rootDir);
		}
		$internalDir.= $dirname .= '/';
		// prevent absolute dirs
		$internalDir = ltrim($internalDir, '/');

		$files= \OC\Files\Filesystem::getDirectoryContent($dir);
		foreach($files as $file) {
			$filename = $file['name'];
			$file = $dir . '/' . $filename;
			if(\OC\Files\Filesystem::is_file($file)) {
				$filesize = \OC\Files\Filesystem::filesize($file);
				$fh = \OC\Files\Filesystem::fopen($file, 'r');
				$this->streamerInstance->addFileFromStream($fh, $internalDir . $filename, $filesize);
				fclose($fh);
			}elseif(\OC\Files\Filesystem::is_dir($file)) {
				$this->addDirRecoursive($file, $internalDir);
			}
		}
	}
	
	public function addFileFromStream($fd, $internalName, $size){
		if ($this->streamerInstance instanceof ZipStreamer) {
			return $this->streamerInstance->addFileFromStream($fd, $internalName);
		} else {
			return $this->streamerInstance->addFileFromStream($fd, $internalName, $size);
		}
	}
	
	public function addEmptyDir($dirName){
		return $this->streamerInstance->addEmptyDir($dirName);
	}
	
	public function finalize(){
		return $this->streamerInstance->finalize();
	}
}
