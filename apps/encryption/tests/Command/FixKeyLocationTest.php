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

	private function markAsEncrypted(TemporaryUnwrapped $storage, string $path, int $unencryptedSize): void {
		$cache = $storage->getCache();
		$cache->update($cache->get($path)->getId(), [
			'encrypted' => 1,
			'unencrypted_size' => $unencryptedSize,
		]);
	}

	/**
	 * A second user whose key tree can hold misplaced keys. The key tree is created
	 * directly, no login needed, the encryption session of the first user stays
	 * untouched.
	 */
	private function setUpSecondUser(): void {
		$this->createUser('test2', 'test2');
	}

	private function moveKeyToSecondUserTree(string $keyPath, string $name): void {
		$rootView = new View();
		foreach (['/test2', '/test2/files_encryption', '/test2/files_encryption/keys'] as $dir) {
			if (!$rootView->file_exists($dir)) {
				$rootView->mkdir($dir);
			}
		}
		$rootView->rename(rtrim($keyPath, '/'), '/test2/files_encryption/keys/' . $name);
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
		$this->markAsEncrypted($strayStorage, 'stray.txt', strlen('secret content'));

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
		$this->markAsEncrypted($strayStorage, 'broken.txt', strlen('secret content'));

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

		$threw = false;
		try {
			self::invokePrivate($command, 'decryptWithSystemKey', [$strayNode, $wrongKey]);
		} catch (\Exception) {
			$threw = true;
		}
		$this->assertTrue($threw, 'decrypting with the wrong key must fail');

		$this->assertTrue($view->file_exists('stray/broken.txt'), 'the original file has to be restored');
		$this->assertSame($cipher, $view->file_get_contents('stray/broken.txt'), 'the original content has to be intact');
		$this->assertFalse($view->file_exists('stray/broken.txt.bak'), 'no .bak must be left behind');

		$systemKeyPath = self::invokePrivate($command, 'getSystemKeyPath', [$strayNode]);
		$systemKeyPathBak = str_replace('broken.txt', 'broken.txt.bak', $systemKeyPath);
		$this->assertFalse($rootView->file_exists($systemKeyPath), 'no temporary system key must be left behind');
		$this->assertFalse($rootView->file_exists($systemKeyPathBak), 'no temporary system key must be left behind');
	}

	/**
	 * A key that only exists in the tree of another user must be found and validated.
	 * The harness mounts are not system wide, the encryption wrapper resolves the keys
	 * of the stray file through the user tree, so the search is exercised with that
	 * staging path, the system wide flow only stages at a different location.
	 */
	public function testKeyFoundInAnotherUsersTree(): void {
		[
			'view' => $view,
			'encryptedBackingStorage' => $encryptedBackingStorage,
		] = $this->setUpMounts();
		$this->setUpSecondUser();

		$view->file_put_contents('enc/original.txt', 'secret content');
		$view->file_put_contents('stray/orphan.txt', $encryptedBackingStorage->file_get_contents('original.txt'));
		$strayStorage = $view->getFileInfo('stray/orphan.txt')->getStorage();
		$this->markAsEncrypted($strayStorage, 'orphan.txt', strlen('secret content'));

		$command = $this->getCommand();
		$user = Server::get(IUserManager::class)->get('test1');
		$userFolder = Server::get(IRootFolder::class)->getUserFolder('test1');
		$originalKey = self::invokePrivate($command, 'getUserKeyPath', [$user, $userFolder->get('enc/original.txt')]);
		// the key sits in ANOTHER user's tree, under the name of the stray file
		$this->moveKeyToSecondUserTree($originalKey, 'orphan.txt');

		$strayNode = $userFolder->get('stray/orphan.txt');
		$stagePath = self::invokePrivate($command, 'getUserKeyPath', [$user, $strayNode]);
		$foundKey = self::invokePrivate($command, 'findKeyInUserTrees', [$user, $strayNode, $stagePath]);

		$this->assertNotNull($foundKey, 'the key in the other tree has to be found');
		$this->assertStringContainsString('/test2/', $foundKey);
	}

	/**
	 * With --personal an encrypted file in the personal space whose key was lost is
	 * restored from another user's tree, healthy files stay untouched.
	 */
	public function testPersonalFileKeyFoundInAnotherUsersTree(): void {
		$this->setUpMounts();
		$this->setUpSecondUser();

		$view = new View('/test1/files');
		$view->file_put_contents('personal.txt', 'personal content');
		$view->file_put_contents('healthy.txt', 'healthy content');

		$command = $this->getCommand();
		$user = Server::get(IUserManager::class)->get('test1');
		$userFolder = Server::get(IRootFolder::class)->getUserFolder('test1');
		$personalKey = self::invokePrivate($command, 'getUserKeyPath', [$user, $userFolder->get('personal.txt')]);
		$this->moveKeyToSecondUserTree($personalKey, 'personal.txt');

		$command->systemMounts = [];
		$tester = new CommandTester($command);
		$exitCode = $tester->execute(['user' => 'test1', '--personal' => true]);
		$display = $tester->getDisplay();

		$this->assertSame(Command::SUCCESS, $exitCode, $display);
		$this->assertStringContainsString('Migrated key from', $display);
		$this->assertEquals('personal content', $view->file_get_contents('personal.txt'));
		$rootView = new View();
		$this->assertTrue($rootView->file_exists($personalKey), 'the key has to be back at the path of the file');
		$this->assertStringNotContainsString('healthy.txt', $display, 'healthy files must not be touched');
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
		$this->markAsEncrypted($strayStorage, 'a-ghost.txt', strlen('gone'));
		// cache row without a backing file, reading it fails like an object store 404
		$strayStorage->unlink('a-ghost.txt');

		$view->file_put_contents('stray/b-plain.txt', 'plain data');
		$this->markAsEncrypted($strayStorage, 'b-plain.txt', strlen('plain data'));

		// ciphertext without any key while the user has no key directory at all,
		// the key search has to come up empty instead of erroring out
		$view->file_put_contents('stray/c-cipher.txt', 'HBEGIN:oc_encryption_module:OC_DEFAULT_MODULE:HEND');
		$this->markAsEncrypted($strayStorage, 'c-cipher.txt', 8);

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
		$this->assertSame(
			0,
			(int)$strayStorage->getCache()->get('b-plain.txt')->getData()['unencrypted_size'],
			'clearing the mark has to reset the leftover unencrypted size as well'
		);
		$this->assertStringContainsString('No key found', $display, 'a missing key directory has to read as "no candidates"');
		$this->assertStringNotContainsString('c-cipher.txt: ', $display, 'the missing key directory failed the file');
	}
}
