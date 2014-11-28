<?php
/**
 * Copyright (c) 2014 Vincent Petry <PVince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;

class NaturalSort {
	private static $instance;
	private $collator;

	/**
	 * Split the given string in chunks of numbers and strings
	 * @param string $t string
	 * @return array of strings and number chunks
	 */
	private function naturalSortChunkify($t) {
		// Adapted and ported to PHP from
		// http://my.opera.com/GreyWyvern/blog/show.dml/1671288
		$tz = array();
		$x = 0;
		$y = -1;
		$n = null;
		$length = strlen($t);

		while ($x < $length) {
			$c = $t[$x];
			// only include the dot in strings
			$m = ((!$n && $c === '.') || ($c >= '0' && $c <= '9'));
			if ($m !== $n) {
				// next chunk
				$y++;
				$tz[$y] = '';
				$n = $m;
			}
			$tz[$y] .= $c;
			$x++;
		}
		return $tz;
	}

	/**
	 * Returns the string collator
	 * @return \Collator string collator
	 */
	private function getCollator() {
		if (!isset($this->collator)) {
			// looks like the default is en_US_POSIX which yields wrong sorting with
			// German umlauts, so using en_US instead
			if (class_exists('Collator')) {
				$this->collator = new \Collator('en_US');
			}
			else {
				$this->collator = new \OC\NaturalSort_DefaultCollator();
			}
		}
		return $this->collator;
	}

	/**
	 * Compare two strings to provide a natural sort
	 * @param string $a first string to compare
	 * @param string $b second string to compare
	 * @return int -1 if $b comes before $a, 1 if $a comes before $b
	 * or 0 if the strings are identical
	 */
	public function compare($a, $b) {
		// Needed because PHP doesn't sort correctly when numbers are enclosed in
		// parenthesis, even with NUMERIC_COLLATION enabled.
		// For example it gave ["test (2).txt", "test.txt"]
		// instead of ["test.txt", "test (2).txt"]
		$aa = self::naturalSortChunkify($a);
		$bb = self::naturalSortChunkify($b);
		$alen = count($aa);
		$blen = count($bb);

		for ($x = 0; $x < $alen && $x < $blen; $x++) {
			$aChunk = $aa[$x];
			$bChunk = $bb[$x];
			if ($aChunk !== $bChunk) {
				if (is_numeric($aChunk) && is_numeric($bChunk)) {
					$aNum = (int)$aChunk;
					$bNum = (int)$bChunk;
					return $aNum - $bNum;
				}
				return self::getCollator()->compare($aChunk, $bChunk);
			}
		}
		return $alen - $blen;
	}

	/**
	 * Returns a singleton
	 * @return \OC\NaturalSort instance
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new \OC\NaturalSort();
		}
		return self::$instance;
	}
}
