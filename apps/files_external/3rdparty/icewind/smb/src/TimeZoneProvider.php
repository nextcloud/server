<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

class TimeZoneProvider implements ITimeZoneProvider {
	/**
	 * @var string[]
	 */
	private $timeZones = [];

	/**
	 * @var ISystem
	 */
	private $system;

	/**
	 * @param ISystem $system
	 */
	public function __construct(ISystem $system) {
		$this->system = $system;
	}

	public function get($host) {
		if (!isset($this->timeZones[$host])) {
			$timeZone = null;
			$net = $this->system->getNetPath();
			// for local domain names we can assume same timezone
			if ($net && $host && strpos($host, '.') !== false) {
				$command = sprintf(
					'%s time zone -S %s',
					$net,
					escapeshellarg($host)
				);
				$timeZone = exec($command);
			}

			if (!$timeZone) {
				$date = $this->system->getDatePath();
				if ($date) {
					$timeZone = exec($date . " +%z");
				} else {
					$timeZone = date_default_timezone_get();
				}
			}
			$this->timeZones[$host] = $timeZone;
		}
		return $this->timeZones[$host];
	}
}
