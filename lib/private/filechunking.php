<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Vincent Petry <pvince81@owncloud.com>
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


class OC_FileChunking {
	protected $info;
	protected $cache;

	static public function decodeName($name) {
		preg_match('/(?P<name>.*)-chunking-(?P<transferid>\d+)-(?P<chunkcount>\d+)-(?P<index>\d+)/', $name, $matches);
		return $matches;
	}

	/**
	 * @param string[] $info
	 */
	public function __construct($info) {
		$this->info = $info;
	}

	public function getPrefix() {
		$name = $this->info['name'];
		$transferid = $this->info['transferid'];

		return $name.'-chunking-'.$transferid.'-';
	}

	protected function getCache() {
		if (!isset($this->cache)) {
			$this->cache = new \OC\Cache\File();
		}
		return $this->cache;
	}

	/**
	 * Stores the given $data under the given $key - the number of stored bytes is returned
	 *
	 * @param string $index
	 * @param resource $data
	 * @return int
	 */
	public function store($index, $data) {
		$cache = $this->getCache();
		$name = $this->getPrefix().$index;
		$cache->set($name, $data);

		return $cache->size($name);
	}

	public function isComplete() {
		$prefix = $this->getPrefix();
		$parts = 0;
		$cache = $this->getCache();
		for($i=0; $i < $this->info['chunkcount']; $i++) {
			if ($cache->hasKey($prefix.$i)) {
				$parts ++;
			}
		}
		return $parts == $this->info['chunkcount'];
	}

	/**
	 * Assembles the chunks into the file specified by the path.
	 * Chunks are deleted afterwards.
	 *
	 * @param string $f target path
	 *
	 * @return integer assembled file size
	 *
	 * @throws \OC\InsufficientStorageException when file could not be fully
	 * assembled due to lack of free space
	 */
	public function assemble($f) {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$count = 0;
		for ($i = 0; $i < $this->info['chunkcount']; $i++) {
			$chunk = $cache->get($prefix.$i);
			// remove after reading to directly save space
			$cache->remove($prefix.$i);
			$count += fwrite($f, $chunk);
		}

		return $count;
	}

	/**
	 * Returns the size of the chunks already present
	 * @return integer size in bytes
	 */
	public function getCurrentSize() {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$total = 0;
		for ($i = 0; $i < $this->info['chunkcount']; $i++) {
			$total += $cache->size($prefix.$i);
		}
		return $total;
	}

	/**
	 * Removes all chunks which belong to this transmission
	 */
	public function cleanup() {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		for($i=0; $i < $this->info['chunkcount']; $i++) {
			$cache->remove($prefix.$i);
		}
	}

	/**
	 * Removes one specific chunk
	 * @param string $index
	 */
	public function remove($index) {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$cache->remove($prefix.$index);
	}

	public function signature_split($orgfile, $input) {
		$info = unpack('n', fread($input, 2));
		$blocksize = $info[1];
		$this->info['transferid'] = mt_rand();
		$count = 0;
		$needed = array();
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		while (!feof($orgfile)) {
			$new_md5 = fread($input, 16);
			if (feof($input)) {
				break;
			}
			$data = fread($orgfile, $blocksize);
			$org_md5 = md5($data, true);
			if ($org_md5 == $new_md5) {
				$cache->set($prefix.$count, $data);
			} else {
				$needed[] = $count;
			}
			$count++;
		}
		return array(
			'transferid' => $this->info['transferid'],
			'needed' => $needed,
			'count' => $count,
		);
	}

	/**
	 * Assembles the chunks into the file specified by the path.
	 * Also triggers the relevant hooks and proxies.
	 *
	 * @param string $path target path
	 *
	 * @return boolean assembled file size or false if file could not be created
	 *
	 * @throws \OC\InsufficientStorageException when file could not be fully
	 * assembled due to lack of free space
	 */
	public function file_assemble($path) {
		$absolutePath = \OC\Files\Filesystem::normalizePath(\OC\Files\Filesystem::getView()->getAbsolutePath($path));
		$data = '';
		// use file_put_contents as method because that best matches what this function does
		if (\OC\Files\Filesystem::isValidPath($path)) {
			$path = \OC\Files\Filesystem::getView()->getRelativePath($absolutePath);
			$exists = \OC\Files\Filesystem::file_exists($path);
			$run = true;
			if(!$exists) {
				OC_Hook::emit(
					\OC\Files\Filesystem::CLASSNAME,
					\OC\Files\Filesystem::signal_create,
					array(
						\OC\Files\Filesystem::signal_param_path => $path,
						\OC\Files\Filesystem::signal_param_run => &$run
					)
				);
			}
			OC_Hook::emit(
				\OC\Files\Filesystem::CLASSNAME,
				\OC\Files\Filesystem::signal_write,
				array(
					\OC\Files\Filesystem::signal_param_path => $path,
					\OC\Files\Filesystem::signal_param_run => &$run
				)
			);
			if(!$run) {
				return false;
			}
			$target = \OC\Files\Filesystem::fopen($path, 'w');
			if($target) {
				$count = $this->assemble($target);
				fclose($target);
				if(!$exists) {
					OC_Hook::emit(
						\OC\Files\Filesystem::CLASSNAME,
						\OC\Files\Filesystem::signal_post_create,
						array( \OC\Files\Filesystem::signal_param_path => $path)
					);
				}
				OC_Hook::emit(
					\OC\Files\Filesystem::CLASSNAME,
					\OC\Files\Filesystem::signal_post_write,
					array( \OC\Files\Filesystem::signal_param_path => $path)
				);
				return $count > 0;
			}else{
				return false;
			}
		}
		return false;
	}
}
