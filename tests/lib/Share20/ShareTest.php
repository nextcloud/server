<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Share20;

use OCP\Files\IRootFolder;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ShareTest
 *
 * @package Test\Share20
 */
class ShareTest extends \Test\TestCase {
	/** @var IRootFolder|MockObject */
	protected $rootFolder;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var \OCP\Share\IShare */
	protected $share;

	protected function setUp(): void {
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->share = new \OC\Share20\Share($this->rootFolder, $this->userManager);
	}


	public function testSetIdInvalid() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('String expected.');

		$this->share->setId(1.2);
	}

	public function testSetIdInt() {
		$this->share->setId(42);
		$this->assertEquals('42', $this->share->getId());
	}


	public function testSetIdString() {
		$this->share->setId('foo');
		$this->assertEquals('foo', $this->share->getId());
	}


	public function testSetIdOnce() {
		$this->expectException(\OCP\Share\Exceptions\IllegalIDChangeException::class);
		$this->expectExceptionMessage('Not allowed to assign a new internal id to a share');

		$this->share->setId('foo');
		$this->share->setId('bar');
	}


	public function testSetProviderIdInt() {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('String expected.');

		$this->share->setProviderId(42);
	}


	public function testSetProviderIdString() {
		$this->share->setProviderId('foo');
		$this->share->setId('bar');
		$this->assertEquals('foo:bar', $this->share->getFullId());
	}


	public function testSetProviderIdOnce() {
		$this->expectException(\OCP\Share\Exceptions\IllegalIDChangeException::class);
		$this->expectExceptionMessage('Not allowed to assign a new provider id to a share');

		$this->share->setProviderId('foo');
		$this->share->setProviderId('bar');
	}
}
