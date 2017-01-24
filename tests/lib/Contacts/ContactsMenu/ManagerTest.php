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

use OC\Contacts\ContactsMenu\ActionProviderStore;
use OC\Contacts\ContactsMenu\ContactsStore;
use OC\Contacts\ContactsMenu\Manager;
use OCP\App\IAppManager;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class ManagerTest extends TestCase {

	/** @var ContactsStore|PHPUnit_Framework_MockObject_MockObject */
	private $contactsStore;

	/** @var IAppManager|PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	/** @var ActionProviderStore|PHPUnit_Framework_MockObject_MockObject */
	private $actionProviderStore;

	/** @var Manager */
	private $manager;

	protected function setUp() {
		parent::setUp();

		$this->contactsStore = $this->createMock(ContactsStore::class);
		$this->actionProviderStore = $this->createMock(ActionProviderStore::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->manager = new Manager($this->contactsStore, $this->actionProviderStore, $this->appManager);
	}

	private function generateTestEntries() {
		$entries = [];
		foreach (range('Z', 'A') as $char) {
			$entry = $this->createMock(IEntry::class);
			$entry->expects($this->any())
				->method('getFullName')
				->willReturn('Contact ' . $char);
			$entries[] = $entry;
		}
		return $entries;
	}

	public function testGetFilteredEntries() {
		$filter = 'con';
		$user = 'user849';
		$entries = $this->generateTestEntries();
		$provider = $this->createMock(IProvider::class);
		$this->contactsStore->expects($this->once())
			->method('getContacts')
			->with($filter)
			->willReturn($entries);
		$this->actionProviderStore->expects($this->once())
			->method('getProviders')
			->willReturn([$provider]);
		$provider->expects($this->exactly(25))
			->method('process');
		$this->appManager->expects($this->once())
			->method('isEnabledForUser')
			->with($this->equalTo('contacts'), $user)
			->willReturn(false);
		$expected = [
			'contacts' => array_slice($entries, 0, 25),
			'contactsAppEnabled' => false,
		];

		$data = $this->manager->getEntries($user, $filter);

		$this->assertEquals($expected, $data);
	}

}
