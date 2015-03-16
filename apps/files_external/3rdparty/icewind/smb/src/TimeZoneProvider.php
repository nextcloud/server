<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

class TimeZoneProvider {
	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var string
	 */
	private $timeZone;

	/**
	 * @param string $host
	 */
	function __construct($host) {
		$this->host = $host;
	}

	public function get() {
		if (!$this->timeZone) {
			$command = 'net time zone -S ' . escapeshellarg($this->host);
			$this->timeZone = exec($command);
		}
		return $this->timeZone;
	}
}
