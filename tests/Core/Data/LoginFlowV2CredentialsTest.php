<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Data;

use JsonSerializable;
use OC\Core\Data\LoginFlowV2Credentials;
use Test\TestCase;

class LoginFlowV2CredentialsTest extends TestCase {
	/** @var \OC\Core\Data\LoginFlowV2Credentials */
	private $fixture;

	public function setUp(): void {
		parent::setUp();

		$this->fixture = new LoginFlowV2Credentials('server', 'login', 'pass');
	}

	public function testImplementsJsonSerializable(): void {
		$this->assertTrue($this->fixture instanceof JsonSerializable);
	}

	/**
	 * Test getter functions.
	 */
	public function testGetter(): void {
		$this->assertEquals('server', $this->fixture->getServer());
		$this->assertEquals('login', $this->fixture->getLoginName());
		$this->assertEquals('pass', $this->fixture->getAppPassword());
	}

	public function testJsonSerialize(): void {
		$this->assertEquals(
			[
				'server' => 'server',
				'loginName' => 'login',
				'appPassword' => 'pass',
			],
			$this->fixture->jsonSerialize()
		);
	}
}
