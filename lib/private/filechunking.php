<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


class OC_FileChunking {
	protected $info;
	protected $cache;

	static public function decodeName($name) {
		preg_match('/(?P<name>.*)-chunking-(?P<transferid>\d+)-(?P<chunkcount>\d+)-(?P<index>\d+)/', $name, $matches);
		return $matches;
	}

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
	 * @param $index
	 * @param $data
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

	public function assemble($f) {
		$cache = $this->getCache();
		$prefix = $this->getPrefix();
		$count = 0;
		for($i=0; $i < $this->info['chunkcount']; $i++) {
			$chunk = $cache->get($prefix.$i);
			$count += fwrite($f, $chunk);
		}

		$this->cleanup();
		return $count;
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
	 * @param $index
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

	public function file_assemble($path) {
		$absolutePath = \OC\Files\Filesystem::normalizePath(\OC\Files\Filesystem::getView()->getAbsolutePath($path));
		$data = '';
		// use file_put_contents as method because that best matches what this function does
		if (OC_FileProxy::runPreProxies('file_put_contents', $absolutePath, $data)
			&& \OC\Files\Filesystem::isValidPath($path)) {
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
				OC_FileProxy::runPostProxies('file_put_contents', $absolutePath, $count);
				return $count > 0;
			}else{
				return false;
			}
		}
	}
}
