<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OC\Files\Notify\Change;
use OC\Files\Notify\RenameChange;
use OCA\Files_External\Lib\Storage\SMB;
use OCP\Files\Notify\IChange;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class SmbTest
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class SmbTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;
	/**
	 * @var SMB instance
	 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->loadConfig('files_external/tests/config.smb.php');
		if (substr($this->config['root'], -1, 1) != '/') {
			$this->config['root'] .= '/';
		}
		$this->config['root'] .= $id; //make sure we have an new empty folder to work in
		$this->instance = new SMB($this->config);
		$this->instance->mkdir('/');
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	public static function directoryProvider(): array {
		// doesn't support leading/trailing spaces
		return [['folder']];
	}

	public function testRenameWithSpaces(): void {
		$this->instance->mkdir('with spaces');
		$result = $this->instance->rename('with spaces', 'foo bar');
		$this->assertTrue($result);
		$this->assertTrue($this->instance->is_dir('foo bar'));
	}

	public function testStorageId(): void {
		$this->instance = new SMB([
			'host' => 'testhost',
			'user' => 'testuser',
			'password' => 'somepass',
			'share' => 'someshare',
			'root' => 'someroot',
		]);
		$this->assertEquals('smb::testuser@testhost//someshare//someroot/', $this->instance->getId());
		$this->instance = null;
	}

	public function testNotifyGetChanges(): void {
		$lastError = null;
		for ($i = 0; $i < 5; $i++) {
			try {
				$this->tryTestNotifyGetChanges();
				return;
			} catch (ExpectationFailedException $e) {
				$lastError = $e;
				$this->tearDown();
				$this->setUp();
				sleep(1);
			}
		}
		throw $lastError;
	}

	private function tryTestNotifyGetChanges(): void {
		$notifyHandler = $this->instance->notify('');
		sleep(1); //give time for the notify to start
		$this->instance->file_put_contents('/newfile.txt', 'test content');
		sleep(1);
		$this->instance->rename('/newfile.txt', 'renamed.txt');
		sleep(1);
		$this->instance->unlink('/renamed.txt');
		sleep(1); //time for all changes to be processed

		/** @var IChange[] $changes */
		$changes = [];
		$count = 0;
		// wait up to 10 seconds for incoming changes
		while (count($changes) < 3 && $count < 10) {
			$changes = array_merge($changes, $notifyHandler->getChanges());
			$count++;
			sleep(1);
		}
		$notifyHandler->stop();

		// depending on the server environment, the initial create might be detected as a change instead
		if ($changes[0]->getType() === IChange::MODIFIED) {
			$expected = [
				new Change(IChange::MODIFIED, 'newfile.txt'),
				new RenameChange(IChange::RENAMED, 'newfile.txt', 'renamed.txt'),
				new Change(IChange::REMOVED, 'renamed.txt')
			];
		} else {
			$expected = [
				new Change(IChange::ADDED, 'newfile.txt'),
				new RenameChange(IChange::RENAMED, 'newfile.txt', 'renamed.txt'),
				new Change(IChange::REMOVED, 'renamed.txt')
			];
		}

		foreach ($expected as $expectedChange) {
			$this->assertTrue(in_array($expectedChange, $changes), "Expected changes are:\n" . print_r($expected, true) . PHP_EOL . 'Expected to find: ' . PHP_EOL . print_r($expectedChange, true) . "\nGot:\n" . print_r($changes, true));
		}
	}

	public function testNotifyListen(): void {
		$notifyHandler = $this->instance->notify('');
		usleep(100 * 1000); //give time for the notify to start
		$this->instance->file_put_contents('/newfile.txt', 'test content');
		$this->instance->unlink('/newfile.txt');
		usleep(100 * 1000); //time for all changes to be processed

		$result = null;

		// since the notify handler buffers until we start listening we will get the above changes
		$notifyHandler->listen(function (IChange $change) use (&$result) {
			$result = $change;
			return false;//stop listening
		});

		// depending on the server environment, the initial create might be detected as a change instead
		if ($result->getType() === IChange::ADDED) {
			$this->assertEquals(new Change(IChange::ADDED, 'newfile.txt'), $result);
		} else {
			$this->assertEquals(new Change(IChange::MODIFIED, 'newfile.txt'), $result);
		}
	}

	public function testRenameRoot(): void {
		// root can't be renamed
		$this->assertFalse($this->instance->rename('', 'foo1'));

		$this->instance->mkdir('foo2');
		$this->assertFalse($this->instance->rename('foo2', ''));
		$this->instance->rmdir('foo2');
	}

	public function testUnlinkRoot(): void {
		// root can't be deleted
		$this->assertFalse($this->instance->unlink(''));
	}

	public function testRmdirRoot(): void {
		// root can't be deleted
		$this->assertFalse($this->instance->rmdir(''));
	}
}
