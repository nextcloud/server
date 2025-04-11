<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\SFTP_Key;

/**
 * Class SFTP_KeyTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class SFTP_KeyTest extends \Test\Files\Storage\Storage {
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.php');
		if (! is_array($this->config) or ! isset($this->config['sftp_key']) or ! $this->config['sftp_key']['run']) {
			$this->markTestSkipped('SFTP with key backend not configured');
		}
		// Make sure we have an new empty folder to work in
		$this->config['sftp_key']['root'] .= '/' . $id;
		$this->instance = new SFTP_Key($this->config['sftp_key']);
		$this->instance->mkdir('/');
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}

	
	public function testInvalidAddressShouldThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);

		// I'd use example.com for this, but someone decided to break the spec and make it resolve
		$this->instance->assertHostAddressValid('notarealaddress...');
	}

	public function testValidAddressShouldPass(): void {
		$this->assertTrue($this->instance->assertHostAddressValid('localhost'));
	}

	
	public function testNegativePortNumberShouldThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->instance->assertPortNumberValid('-1');
	}

	
	public function testNonNumericalPortNumberShouldThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);

		$this->instance->assertPortNumberValid('a');
	}

	
	public function testHighPortNumberShouldThrowException(): void {
		$this->expectException(\InvalidArgumentException::class);
 
		$this->instance->assertPortNumberValid('65536');
	}

	public function testValidPortNumberShouldPass(): void {
		$this->assertTrue($this->instance->assertPortNumberValid('22222'));
	}
}
