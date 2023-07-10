<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\encryption\tests;

use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Files\View;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IDisableEncryptionStorage;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

class TemporaryNoEncrypted extends Temporary implements IDisableEncryptionStorage {

}

/**
 * @group DB
 */
class EncryptedStorageTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	public function testMoveFromEncrypted() {
		$this->createUser("test1", "test2");
		$this->setupForUser("test1", 'test2');

		$unwrapped = new Temporary();

		$this->registerMount("test1", new TemporaryNoEncrypted(), "/test1/files/unenc");
		$this->registerMount("test1", $unwrapped, "/test1/files/enc");

		$this->loginWithEncryption("test1");

		$view = new View("/test1/files");

		/** @var IMountManager $mountManager */
		$mountManager = \OC::$server->get(IMountManager::class);

		$encryptedMount = $mountManager->find("/test1/files/enc");
		$unencryptedMount = $mountManager->find("/test1/files/unenc");
		$encryptedStorage = $encryptedMount->getStorage();
		$unencryptedStorage = $unencryptedMount->getStorage();
		$encryptedCache = $encryptedStorage->getCache();
		$unencryptedCache = $unencryptedStorage->getCache();

		$this->assertTrue($encryptedStorage->instanceOfStorage(Encryption::class));
		$this->assertFalse($unencryptedStorage->instanceOfStorage(Encryption::class));

		$encryptedStorage->file_put_contents("foo.txt", "bar");
		$this->assertEquals("bar", $encryptedStorage->file_get_contents("foo.txt"));
		$this->assertStringStartsWith("HBEGIN:oc_encryption_module:", $unwrapped->file_get_contents("foo.txt"));

		$this->assertTrue($encryptedCache->get("foo.txt")->isEncrypted());

		$view->rename("enc/foo.txt", "unenc/foo.txt");

		$this->assertEquals("bar", $unencryptedStorage->file_get_contents("foo.txt"));
		$this->assertFalse($unencryptedCache->get("foo.txt")->isEncrypted());
	}
}
