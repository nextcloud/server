<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Native;

use Icewind\SMB\AbstractServer;
use Icewind\SMB\IAuth;
use Icewind\SMB\IOptions;
use Icewind\SMB\ISystem;
use Icewind\SMB\TimeZoneProvider;

class NativeServer extends AbstractServer {
	/**
	 * @var NativeState
	 */
	protected $state;

	public function __construct($host, IAuth $auth, ISystem $system, TimeZoneProvider $timeZoneProvider, IOptions $options) {
		parent::__construct($host, $auth, $system, $timeZoneProvider, $options);
		$this->state = new NativeState();
	}

	protected function connect() {
		$this->state->init($this->getAuth(), $this->getOptions());
	}

	/**
	 * @return \Icewind\SMB\IShare[]
	 * @throws \Icewind\SMB\Exception\AuthenticationException
	 * @throws \Icewind\SMB\Exception\InvalidHostException
	 */
	public function listShares() {
		$this->connect();
		$shares = [];
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
	 * @param ISystem $system
	 * @return bool
	 */
	public static function available(ISystem $system) {
		return $system->libSmbclientAvailable();
	}
}
