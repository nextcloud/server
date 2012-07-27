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
		$chunkcount = $this->info['chunkcount'];

		return $name.'-chunking-'.$transferid.'-'.$chunkcount.'-';
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
}
