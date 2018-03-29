<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB;

class NativeServer extends Server {
	/**
	 * @var \Icewind\SMB\NativeState
	 */
	protected $state;

	/**
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 */
	public function __construct($host, $user, $password) {
		parent::__construct($host, $user, $password);
		$this->state = new NativeState();
	}

	protected function connect() {
		$this->state->init($this->getWorkgroup(), $this->getUser(), $this->getPassword());
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
}
