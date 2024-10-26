<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\LoginCredentials;

use OC\Authentication\LoginCredentials\Credentials;
use Test\TestCase;

class CredentialsTest extends TestCase {
	/** @var string */
	private $uid;

	/** @var string */
	private $user;

	/** @var string */
	private $password;

	/** @var Credentials */
	private $credentials;

	protected function setUp(): void {
		parent::setUp();

		$this->uid = 'user123';
		$this->user = 'User123';
		$this->password = '123456';

		$this->credentials = new Credentials($this->uid, $this->user, $this->password);
	}

	public function testGetUID(): void {
		$this->assertEquals($this->uid, $this->credentials->getUID());
	}

	public function testGetUserName(): void {
		$this->assertEquals($this->user, $this->credentials->getLoginName());
	}

	public function testGetPassword(): void {
		$this->assertEquals($this->password, $this->credentials->getPassword());
	}
}
