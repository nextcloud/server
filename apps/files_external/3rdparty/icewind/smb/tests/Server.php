<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\SMB\Test;

class Server extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \Icewind\SMB\Server $server
	 */
	private $server;

	private $config;

	public function setUp() {
		$this->config = json_decode(file_get_contents(__DIR__ . '/config.json'));
		$this->server = new \Icewind\SMB\Server($this->config->host, $this->config->user, $this->config->password);
	}

	public function testListShares() {
		$shares = $this->server->listShares();
		foreach ($shares as $share) {
			if ($share->getName() === $this->config->share) {
				return;
			}
		}
		$this->fail('Share "' . $this->config->share . '" not found');
	}

	/**
	 * @expectedException \Icewind\SMB\Exception\AuthenticationException
	 */
	public function testWrongUserName() {
		$this->markTestSkipped('This fails for no reason on travis');
		$server = new \Icewind\SMB\Server($this->config->host, uniqid(), uniqid());
		$server->listShares();
	}

	/**
	 * @expectedException \Icewind\SMB\Exception\AuthenticationException
	 */
	public function testWrongPassword() {
		$server = new \Icewind\SMB\Server($this->config->host, $this->config->user, uniqid());
		$server->listShares();
	}

	/**
	 * @expectedException \Icewind\SMB\Exception\InvalidHostException
	 */
	public function testWrongHost() {
		$server = new \Icewind\SMB\Server(uniqid(), $this->config->user, $this->config->password);
		$server->listShares();
	}
}
