<?php
/**
 * Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files;

use OC\AllConfig;
use OC\Files\FileInfo;
use OC\Files\Storage\Home;
use OC\Files\Storage\Temporary;
use OC\User\User;
use OCP\IConfig;
use Test\TestCase;
use Test\Traits\UserTrait;

class FileInfoTest extends TestCase {
	use UserTrait;

	private $config;

	public function setUp() {
		parent::setUp();
		$this->createUser('foo', 'foo');
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
	}

	public function testIsMountedHomeStorage() {
		$fileInfo = new FileInfo(
			'',
			new Home(['user' => new User('foo', $this->userBackend, null, $this->config)]),
			'', [], null);
		$this->assertFalse($fileInfo->isMounted());
	}

	public function testIsMountedNonHomeStorage() {
		$fileInfo = new FileInfo(
			'',
			new Temporary(),
			'', [], null);
		$this->assertTrue($fileInfo->isMounted());
	}
}
