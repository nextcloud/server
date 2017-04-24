<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace Tests\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\ContactsStore;
use OCP\Contacts\IManager;
use OCP\IUser;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class ContactsStoreTest extends TestCase {

	/** @var ContactsStore */
	private $contactsStore;

	/** @var IManager|PHPUnit_Framework_MockObject_MockObject */
	private $contactsManager;

	protected function setUp() {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);

		$this->contactsStore = new ContactsStore($this->contactsManager);
	}

	public function testGetContactsWithoutFilter() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
				],
		]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertEquals([
			'darren@roner.au'
			], $entries[1]->getEMailAddresses());
	}

	public function testGetContactsHidesOwnEntry() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'UID' => 'user123',
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
				],
		]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(1, $entries);
	}

	public function testGetContactsWithoutBinaryImage() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => base64_encode('photophotophoto'),
				],
		]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertNull($entries[1]->getAvatar());
	}

	public function testGetContactsWithoutAvatarURI() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'UID' => 123,
				],
				[
					'UID' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => 'VALUE=uri:https://photo',
				],
		]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entries = $this->contactsStore->getContacts($user, '');

		$this->assertCount(2, $entries);
		$this->assertEquals('https://photo', $entries[1]->getAvatar());
	}

	public function testFindOneUser() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('a567'), $this->equalTo(['UID']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'isLocalSystemBook' => true
				],
			]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entry = $this->contactsStore->findOne($user, 0, 'a567');

		$this->assertEquals([
			'darren@roner.au'
		], $entry->getEMailAddresses());
	}

	public function testFindOneEMail() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('darren@roner.au'), $this->equalTo(['EMAIL']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'isLocalSystemBook' => false
				],
			]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entry = $this->contactsStore->findOne($user, 4, 'darren@roner.au');

		$this->assertEquals([
			'darren@roner.au'
		], $entry->getEMailAddresses());
	}

	public function testFindOneNotSupportedType() {
		$user = $this->createMock(IUser::class);

		$entry = $this->contactsStore->findOne($user, 42, 'darren@roner.au');

		$this->assertEquals(null, $entry);
	}

	public function testFindOneNoMatches() {
		$user = $this->createMock(IUser::class);
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo('a567'), $this->equalTo(['UID']))
			->willReturn([
				[
					'UID' => 123,
					'isLocalSystemBook' => false
				],
				[
					'UID' => 'a567',
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au123'
					],
					'isLocalSystemBook' => false
				],
			]);
		$user->expects($this->once())
			->method('getUID')
			->willReturn('user123');

		$entry = $this->contactsStore->findOne($user, 0, 'a567');

		$this->assertEquals(null, $entry);
	}
}
