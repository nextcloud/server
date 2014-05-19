<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
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
	private $log;

	/**
	 * @param string $search the search term as was given by the user
	 * @param string $key the array key containing the value that should be compared
	 * against
	 * @param string $encoding optional, encoding to use, defaults to UTF-8
	 * @param \OC\Log $log optional
	 */
	public function __construct($search, $key, \OC\Log $log = null, $encoding = 'UTF-8') {
		$this->encoding = $encoding;
		$this->key = $key;
		$this->log = $log;
		$this->search = mb_strtolower($search, $this->encoding);
	}

	/**
	 * User and Group names matching the search term at the beginning shall appear
	 * on top of the share dialog. Following entries in alphabetical order.
	 * Callback function for usort. http://php.net/usort
	 */
	public function sort($a, $b) {
		if(!isset($a[$this->key]) || !isset($b[$this->key])) {
			if(!is_null($this->log)) {
				$this->log->error('Sharing dialogue: cannot sort due to ' .
								  'missing array key', array('app' => 'core'));
			}
			return 0;
		}
		$nameA = mb_strtolower($a[$this->key], $this->encoding);
		$nameB = mb_strtolower($b[$this->key], $this->encoding);
		$i = mb_strpos($nameA, $this->search, 0, $this->encoding);
		$j = mb_strpos($nameB, $this->search, 0, $this->encoding);

		if($i === $j || $i > 0 && $j > 0) {
			return strcmp(mb_strtolower($nameA, $this->encoding),
						  mb_strtolower($nameB, $this->encoding));
		} elseif ($i === 0) {
			return -1;
		} else {
			return 1;
		}
	}
}

