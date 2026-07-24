<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Tests\Command;

use OC\Encryption\Util as EncryptionUtil;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Encryption\Command\FixKeyLocation;
use OCA\Encryption\KeyManager;
use OCP\Encryption\IManager;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

class TemporaryUnwrapped extends Temporary implements IDisableEncryptionStorage {

}

class TestableFixKeyLocation extends FixKeyLocation {
	/** @var ICachedMountInfo[] */
	public array $systemMounts = [];

	protected function getSystemMountsForUser(IUser $user): array {
		return $this->systemMounts;
	}
}

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class FixKeyLocationTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	/**
	 * One mount with the encryption wrapper and one without, so ciphertext can be
	 * placed on a mount that cannot decrypt it, like a broken cross storage move
	 * leaves it behind.
	 *
	 * @return array{view: View, encryptedBackingStorage: Temporary, strayStorage: TemporaryUnwrapped}
	 */
	private function setUpMounts(): array {
		Server::get(KeyManager::class)->validateMasterKey();
		Server::get(KeyManager::class)->validateShareKey();
		$this->createUser('test1', 'test2');
		$this->setupForUser('test1', 'test2');

		$encryptedBacking = new Temporary();
		$stray = new TemporaryUnwrapped();

		$this->registerMount('test1', $encryptedBacking, '/test1/files/enc');
		$this->registerMount('test1', $stray, '/test1/files/stray');

		$this->loginWithEncryption('test1');

		return [
			'view' => new View('/test1/files'),
			'encryptedBackingStorage' => $encryptedBacking,
			'strayStorage' => $stray,
		];
	}

	private function getCommand(): TestableFixKeyLocation {
		return new TestableFixKeyLocation(
			Server::get(IUserManager::class),
			Server::get(IUserMountCache::class),
			Server::get(EncryptionUtil::class),
			Server::get(IRootFolder::class),
			Server::get(LoggerInterface::class),
			Server::get(IManager::class),
		);
	}

	private function markAsEncrypted(TemporaryUnwrapped $storage, string $path): void {
		$cache = $storage->getCache();
		$cache->update($cache->get($path)->getId(), ['encrypted' => 1]);
	}

	/**
	 * Key validation must read through the encryption wrapper: a raw read succeeds
	 * with any key, so on an unwrapped mount the first candidate key would always
	 * "validate" and a wrong key only fails later, during the actual decryption.
	 */
	public function testKeyValidationReadsThroughEncryption(): void {
		[
			'view' => $view,
			'encryptedBackingStorage' => $encryptedBackingStorage,
		] = $this->setUpMounts();

		$view->file_put_contents('enc/original.txt', 'secret content');
		// ciphertext under a path that has no key
		$view->file_put_contents('stray/stray.txt', $encryptedBackingStorage->file_get_contents('original.txt'));
		$strayStorage = $view->getFileInfo('stray/stray.txt')->getStorage();
		$this->markAsEncrypted($strayStorage, 'stray.txt');

		$userFolder = Server::get(IRootFolder::class)->getUserFolder('test1');
		$command = $this->getCommand();

		$this->assertFalse(
			self::invokePrivate($command, 'tryReadFile', [$userFolder->get('stray/stray.txt')]),
			'ciphertext without a key must not validate'
		);
		$this->assertTrue(
			self::invokePrivate($command, 'tryReadFile', [$userFolder->get('enc/original.txt')]),
			'a readable encrypted file must validate'
		);
	}

	/**
	 * A failed decryption must roll everything back: no .bak left behind, no partial
	 * target, no temporary system key.
	 */
	public function testFailedDecryptionRollsBack(): void {
		[
			'view' => $view,
			'encryptedBackingStorage' => $encryptedBackingStorage,
		] = $this->setUpMounts();

		$view->file_put_contents('enc/original.txt', 'secret content');
		$view->file_put_contents('enc/other.txt', 'other content');
		$cipher = $encryptedBackingStorage->file_get_contents('original.txt');
		$view->file_put_contents('stray/broken.txt', $cipher);
		$strayStorage = $view->getFileInfo('stray/broken.txt')->getStorage();
		$this->markAsEncrypted($strayStorage, 'broken.txt');

		$userFolder = Server::get(IRootFolder::class)->getUserFolder('test1');
		$strayNode = $userFolder->get('stray/broken.txt');
		$command = $this->getCommand();
		$user = Server::get(IUserManager::class)->get('test1');

		// the key of a different file cannot decrypt this ciphertext
		$wrongKey = self::invokePrivate($command, 'getUserKeyPath', [$user, $userFolder->get('enc/other.txt')]);
		$rootView = new View();
		$this->assertTrue($rootView->file_exists($wrongKey), 'test setup: key of the other file has to exist');
		// the decryption reads under the .bak name, stage the wrong key where the
		// wrapper will look for it so the failure is a real signature mismatch
		$brokenKeyPath = self::invokePrivate($command, 'getUserKeyPath', [$user, $strayNode]);
		$rootView->copy($wrongKey, str_replace('broken.txt', 'broken.txt.bak', $brokenKeyPath));

		try {
			self::invokePrivate($command, 'decryptWithSystemKey', [$strayNode, $wrongKey]);
			$this->fail('decrypting with the wrong key must fail');
		} catch (\Exception $e) {
		}

		$this->assertTrue($view->file_exists('stray/broken.txt'), 'the original file has to be restored');
		$this->assertSame($cipher, $view->file_get_contents('stray/broken.txt'), 'the original content has to be intact');
		$this->assertFalse($view->file_exists('stray/broken.txt.bak'), 'no .bak must be left behind');

		$systemKeyPath = self::invokePrivate($command, 'getSystemKeyPath', [$strayNode]);
		$systemKeyPathBak = str_replace('broken.txt', 'broken.txt.bak', $systemKeyPath);
		$this->assertFalse($rootView->file_exists($systemKeyPath), 'no temporary system key must be left behind');
		$this->assertFalse($rootView->file_exists($systemKeyPathBak), 'no temporary system key must be left behind');
	}

	/**
	 * One broken file must not abort the whole run, the remaining files still get
	 * processed and the failure is reported.
	 */
	public function testExecuteContinuesAfterFailure(): void {
		[
			'view' => $view,
		] = $this->setUpMounts();

		$view->file_put_contents('stray/a-ghost.txt', 'gone');
		$strayStorage = $view->getFileInfo('stray/a-ghost.txt')->getStorage();
		$this->markAsEncrypted($strayStorage, 'a-ghost.txt');
		// cache row without a backing file, reading it fails like an object store 404
		$strayStorage->unlink('a-ghost.txt');

		$view->file_put_contents('stray/b-plain.txt', 'plain data');
		$this->markAsEncrypted($strayStorage, 'b-plain.txt');

		// ciphertext without any key while the user has no key directory at all,
		// the key search has to come up empty instead of erroring out
		$view->file_put_contents('stray/c-cipher.txt', 'HBEGIN:oc_encryption_module:OC_DEFAULT_MODULE:HEND');
		$this->markAsEncrypted($strayStorage, 'c-cipher.txt');

		$command = $this->getCommand();
		$mount = $this->createMock(ICachedMountInfo::class);
		$mount->method('getMountPoint')->willReturn('/test1/files/stray/');
		$command->systemMounts = [$mount];

		$tester = new CommandTester($command);
		$exitCode = $tester->execute(['user' => 'test1']);
		$display = $tester->getDisplay();

		$this->assertSame(Command::FAILURE, $exitCode, 'failures have to be reflected in the exit code');
		$this->assertStringContainsString('Failed to process', $display);
		$this->assertStringContainsString('could not be processed', $display);
		$this->assertStringContainsString('  - /test1/files/stray/a-ghost.txt', $display, 'the summary has to name the affected file');
		$this->assertFalse(
			$strayStorage->getCache()->get('b-plain.txt')->isEncrypted(),
			'the remaining file was not processed, the run stopped at the broken one'
		);
		$this->assertStringContainsString('No key found', $display, 'a missing key directory has to read as "no candidates"');
		$this->assertStringNotContainsString('c-cipher.txt: ', $display, 'the missing key directory failed the file');
	}
}
