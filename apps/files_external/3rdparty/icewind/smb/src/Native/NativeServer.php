<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\AbstractServer;
use Icewind\SMB\IAuth;
use Icewind\SMB\System;
use Icewind\SMB\TimeZoneProvider;

class NativeServer extends AbstractServer {
	/**
	 * @var NativeState
	 */
	protected $state;

	/**
	 * @param string $host
	 * @param IAuth $auth
	 * @param System $system
	 * @param TimeZoneProvider $timeZoneProvider
	 */
	public function __construct($host, IAuth $auth, System $system, TimeZoneProvider $timeZoneProvider) {
		parent::__construct($host, $auth, $system, $timeZoneProvider);
		$this->state = new NativeState();
	}

	protected function connect() {
		$this->state->init($this->getAuth());
	}

	/**
	 * @return \Icewind\SMB\IShare[]
	 * @throws \Icewind\SMB\Exception\AuthenticationException
	 * @throws \Icewind\SMB\Exception\InvalidHostException
	 */
	public function listShares() {
		$this->connect();
		$shares = array();
		$dh = $this->state->opendir('smb://' . $this->getHost());
		while ($share = $this->state->readdir($dh)) {
			if ($share['type'] === 'file share') {
				$shares[] = $this->getShare($share['name']);
			}
		}
		$this->state->closedir($dh);
		return $shares;
	}

	/**
	 * @param string $name
	 * @return \Icewind\SMB\IShare
	 */
	public function getShare($name) {
		return new NativeShare($this, $name);
	}

	/**
	 * Check if the smbclient php extension is available
	 *
	 * @param System $system
	 * @return bool
	 */
	public static function available(System $system) {
		return function_exists('smbclient_state_new');
	}
}
