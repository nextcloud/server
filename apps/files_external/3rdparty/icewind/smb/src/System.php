<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\Exception;

class System {
	private $smbclient;

	private $net;

	public static function getFD($num) {
		$folders = array(
			'/proc/self/fd',
			'/dev/fd'
		);
		foreach ($folders as $folder) {
			if (file_exists($folder)) {
				return $folder . '/' . $num;
			}
		}
		throw new Exception('Cant find file descriptor path');
	}

	public function getSmbclientPath() {
		if (!$this->smbclient) {
			$this->smbclient = trim(`which smbclient`);
		}
		return $this->smbclient;
	}

	public function getNetPath() {
		if (!$this->net) {
			$this->net = trim(`which net`);
		}
		return $this->net;
	}
}
