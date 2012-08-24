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
			$this->cache = new OC_Cache_File();
		}
		return $this->cache;
	}

	public function store($index, $data) {
		$cache = $this->getCache();
		$name = $this->getPrefix().$index;
		$cache->set($name, $data);
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
		for($i=0; $i < $this->info['chunkcount']; $i++) {
			$chunk = $cache->get($prefix.$i);
			$cache->remove($prefix.$i);
			fwrite($f,$chunk);
		}
		fclose($f);
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
}
