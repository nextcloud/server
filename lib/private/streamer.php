<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use ownCloud\TarStreamer\TarStreamer;
use ZipStreamer\ZipStreamer;

class Streamer {
	// array of regexp. Matching user agents will get tar instead of zip
	private $preferTarFor = [ '/macintosh|mac os x/i' ];

	// streamer instance
	private $streamerInstance;
	
	public function __construct(){
		/** @var \OCP\IRequest */
		$request = \OC::$server->getRequest();
		
		if ($request->isUserAgent($this->preferTarFor)) {
			$this->streamerInstance = new TarStreamer();
		} else {
			$this->streamerInstance = new ZipStreamer(['zip64' => PHP_INT_SIZE !== 4]);
		}
	}
	
	/**
	 * Send HTTP headers
	 * @param string $name 
	 */
	public function sendHeaders($name){
		$extension = $this->streamerInstance instanceof ZipStreamer ? '.zip' : '.tar';
		$fullName = $name . $extension;
		$this->streamerInstance->sendHeaders($fullName);
	}
	
	/**
	 * Stream directory recursively
	 * @param string $dir
	 * @param string $internalDir
	 */
	public function addDirRecursive($dir, $internalDir='') {
		$dirname = basename($dir);
		$rootDir = $internalDir . $dirname;
		if (!empty($rootDir)) {
			$this->streamerInstance->addEmptyDir($rootDir);
		}
		$internalDir .= $dirname . '/';
		// prevent absolute dirs
		$internalDir = ltrim($internalDir, '/');

		$files= \OC\Files\Filesystem::getDirectoryContent($dir);
		foreach($files as $file) {
			$filename = $file['name'];
			$file = $dir . '/' . $filename;
			if(\OC\Files\Filesystem::is_file($file)) {
				$filesize = \OC\Files\Filesystem::filesize($file);
				$fh = \OC\Files\Filesystem::fopen($file, 'r');
				$this->addFileFromStream($fh, $internalDir . $filename, $filesize);
				fclose($fh);
			}elseif(\OC\Files\Filesystem::is_dir($file)) {
				$this->addDirRecursive($file, $internalDir);
			}
		}
	}
	
	/**
	 * Add a file to the archive at the specified location and file name.
	 *
	 * @param string $stream Stream to read data from
	 * @param string $internalName Filepath and name to be used in the archive.
	 * @param int $size Filesize
	 * @return bool $success
	 */
	public function addFileFromStream($stream, $internalName, $size){
		if ($this->streamerInstance instanceof ZipStreamer) {
			return $this->streamerInstance->addFileFromStream($stream, $internalName);
		} else {
			return $this->streamerInstance->addFileFromStream($stream, $internalName, $size);
		}
	}

	/**
	 * Add an empty directory entry to the archive.
	 *
	 * @param string $dirName Directory Path and name to be added to the archive.
	 * @return bool $success
	 */
	public function addEmptyDir($dirName){
		return $this->streamerInstance->addEmptyDir($dirName);
	}

	/**
	 * Close the archive.
	 * A closed archive can no longer have new files added to it. After
	 * closing, the file is completely written to the output stream.
	 * @return bool $success
	 */
	public function finalize(){
		return $this->streamerInstance->finalize();
	}
}
