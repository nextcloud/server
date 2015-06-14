<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage\PolyFill;

trait CopyDirectory {
	abstract public function is_dir($path);

	abstract public function file_exists($path);

	abstract public function buildPath($path);

	abstract public function unlink($path);

	abstract public function opendir($path);

	abstract public function mkdir($path);

	public function copy($source, $target) {
		if ($this->is_dir($source)) {
			if ($this->file_exists($target)) {
				$this->unlink($target);
			}
			parent::copy($source, $target);
			return $this->copyRecursive($source, $target);
		} else {
			return parent::copy($source, $target);
		}
	}

	/**
	 * For adapters that dont support copying folders natively
	 *
	 * @param $source
	 * @param $target
	 * @return bool
	 */
	protected function copyRecursive($source, $target) {
		$dh = $this->opendir($source);
		$result = true;
		while ($file = readdir($dh)) {
			if ($file !== '.' and $file !== '..') {
				if ($this->is_dir($source . '/' . $file)) {
					$this->mkdir($target . '/' . $file);
					$result = $this->copyRecursive($source . '/' . $file, $target . '/' . $file);
				} else {
					$result = parent::copy($source . '/' . $file, $target . '/' . $file);
				}
				if (!$result) {
					break;
				}
			}
		}
		return $result;
	}
}
