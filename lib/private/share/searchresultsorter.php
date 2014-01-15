<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.bzoc>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */
namespace OC\Share;

class SearchResultSorter {
	private $search;
	private $encoding;
	private $key;

	/**
	 * @param $search the search term as was given by the user
	 * @param $key the array key containing the value that should be compared
	 * against
	 * @param $encoding optional, encoding to use, defaults to UTF-8
	 */
	public function __construct($search, $key, $encoding = 'UTF-8') {
		$this->encoding = $encoding;
		$this->key = $key;
		$this->search = mb_strtolower($search, $this->encoding);
	}

	/**
	 * User and Group names matching the search term at the beginning shall appear
	 * on top of the share dialog.
	 * Callback function for usort. http://php.net/usort
	 */
	public function sort($a, $b) {
		if(!isset($a[$this->key]) || !isset($b[$this->key])) {
			\OCP\Util::writeLog('core', 'Sharing: cannot sort due to missing'.
										'array key', \OC_Log::ERROR);
			return 0;
		}
		$nameA = mb_strtolower($a[$this->key], $this->encoding);
		$nameB = mb_strtolower($b[$this->key], $this->encoding);
		$i = mb_strpos($nameA, $this->search, 0, $this->encoding);
		$j = mb_strpos($nameB, $this->search, 0, $this->encoding);

		if($i === $j) {
			return 0;
		} elseif ($i === 0) {
			return -1;
		} else {
			return 1;
		}
	}
}

