<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lockdown\Filesystem;

use OC\Authentication\Token\PublicKeyToken;
use OC\Files\Filesystem;
use OC\Lockdown\Filesystem\NullStorage;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class NoFSTest extends \Test\TestCase {
	use UserTrait;

	protected function tearDown(): void {
		$token = new PublicKeyToken();
		$token->setScope([
			'filesystem' => true
		]);
		\OC::$server->get('LockdownManager')->setToken($token);
		parent::tearDown();
	}

	protected function setUp(): void {
		parent::setUp();
		$token = new PublicKeyToken();
		$token->setScope([
			'filesystem' => false
		]);

		\OC::$server->get('LockdownManager')->setToken($token);
		$this->createUser('foo', 'var');
	}

	public function testSetupFS() {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS('foo');

		$this->assertInstanceOf(NullStorage::class, Filesystem::getStorage('/foo/files'));
	}
}
