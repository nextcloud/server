<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


class OC_FileChunking {
	static public function decodeName($name) {
		preg_match('/(?P<name>.*)-chunking-(?P<transferid>\d+)-(?P<chunkcount>\d+)-(?P<index>\d+)/', $name, $matches);
		return $matches;
	}

	static public function getPrefix($name, $transferid, $chunkcount) {
		return $name.'-chunking-'.$transferid.'-'.$chunkcount.'-';
	}

	static public function store($name, $data) {
		$cache = new OC_Cache_File();
		$cache->set($name, $data);
	}

	static public function isComplete($info) {
		$prefix = OC_FileChunking::getPrefix($info['name'],
				$info['transferid'],
				$info['chunkcount']
			);
		$parts = 0;
		$cache = new OC_Cache_File();
		for($i=0; $i < $info['chunkcount']; $i++) {
			if ($cache->hasKey($prefix.$i)) {
				$parts ++;
			}
		}
		return $parts == $info['chunkcount'];
	}

	static public function assemble($info, $f) {
		$cache = new OC_Cache_File();
		$prefix = OC_FileChunking::getPrefix($info['name'],
				$info['transferid'],
				$info['chunkcount']
			);
		for($i=0; $i < $info['chunkcount']; $i++) {
			$chunk = $cache->get($prefix.$i);
			$cache->remove($prefix.$i);
			fwrite($f,$chunk);
		}
		fclose($f);
	}
}
