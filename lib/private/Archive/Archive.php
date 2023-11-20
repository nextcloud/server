<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Archive;

abstract class Archive {
	abstract public function __construct(string $source);

	/**
	 * add an empty folder to the archive
	 */
	abstract public function addFolder(string $path): bool;

	/**
	 * add a file to the archive
	 * @param string $source either a local file or string data
	 */
	abstract public function addFile(string $path, string $source = ''): bool;

	/**
	 * rename a file or folder in the archive
	 */
	abstract public function rename(string $source, string $dest): bool;

	/**
	 * get the uncompressed size of a file in the archive
	 */
	abstract public function filesize(string $path): false|int|float;

	/**
	 * get the last modified time of a file in the archive
	 * @return int|false
	 */
	abstract public function mtime(string $path);

	/**
	 * get the files in a folder
	 * @param string $path
	 * @return array
	 */
	abstract public function getFolder(string $path): array;

	/**
	 * get all files in the archive
	 */
	abstract public function getFiles(): array;

	/**
	 * get the content of a file
	 * @return string|false
	 */
	abstract public function getFile(string $path);

	/**
	 * extract a single file from the archive
	 */
	abstract public function extractFile(string $path, string $dest): bool;

	/**
	 * extract the archive
	 */
	abstract public function extract(string $dest): bool;

	/**
	 * check if a file or folder exists in the archive
	 */
	abstract public function fileExists(string $path): bool;

	/**
	 * remove a file or folder from the archive
	 */
	abstract public function remove(string $path): bool;

	/**
	 * get a file handler
	 * @return bool|resource
	 */
	abstract public function getStream(string $path, string $mode);

	/**
	 * add a folder and all its content
	 */
	public function addRecursive(string $path, string $source): void {
		$dh = opendir($source);
		if (is_resource($dh)) {
			$this->addFolder($path);
			while (($file = readdir($dh)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				if (is_dir($source.'/'.$file)) {
					$this->addRecursive($path.'/'.$file, $source.'/'.$file);
				} else {
					$this->addFile($path.'/'.$file, $source.'/'.$file);
				}
			}
		}
	}
}
