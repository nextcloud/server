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
	 * @var System
	 */
	private $system;

	/**
	 * @param string $host
	 * @param System $system
	 */
	function __construct($host, System $system) {
		$this->host = $host;
		$this->system = $system;
	}

	public function get() {
		if (!$this->timeZone) {
			$net = $this->system->getNetPath();
			if ($net) {
				$command = sprintf('%s time zone -S %s',
					$net,
					escapeshellarg($this->host)
				);
				$this->timeZone = exec($command);
			} else { // fallback to server timezone
				$this->timeZone = date_default_timezone_get();
			}
		}
		return $this->timeZone;
	}
}
