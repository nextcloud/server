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
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'id' => 123,
				],
				[
					'id' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
				],
		]);

		$entries = $this->contactsStore->getContacts('');

		$this->assertCount(2, $entries);
		$this->assertEquals([
			'darren@roner.au'
			], $entries[1]->getEMailAddresses());
	}

	public function testGetContactsWithoutBinaryImage() {
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'id' => 123,
				],
				[
					'id' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => base64_encode('photophotophoto'),
				],
		]);

		$entries = $this->contactsStore->getContacts('');

		$this->assertCount(2, $entries);
		$this->assertNull($entries[1]->getAvatar());
	}

	public function testGetContactsWithoutAvatarURI() {
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($this->equalTo(''), $this->equalTo(['FN']))
			->willReturn([
				[
					'id' => 123,
				],
				[
					'id' => 567,
					'FN' => 'Darren Roner',
					'EMAIL' => [
						'darren@roner.au'
					],
					'PHOTO' => 'VALUE=uri:https://photo',
				],
		]);

		$entries = $this->contactsStore->getContacts('');

		$this->assertCount(2, $entries);
		$this->assertEquals('https://photo', $entries[1]->getAvatar());
	}

}
