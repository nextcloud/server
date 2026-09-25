<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\ActionProviderStore;
use OC\Contacts\ContactsMenu\ContactsStore;
use OC\Contacts\ContactsMenu\Entry;
use OC\Contacts\ContactsMenu\Manager;
use OCP\App\IAppManager;
use OCP\Constants;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IConfig;
use OCP\IUser;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Manager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createInstanceWithMocks(Manager::class);
	}

	private function generateTestEntries(): array {
		$entries = [];
		foreach (range('Z', 'A') as $char) {
			$entry = $this->createMock(Entry::class);
			$entry->expects($this->any())
				->method('getFullName')
				->willReturn('Contact ' . $char);
			$entries[] = $entry;
		}
		return $entries;
	}

	public function testGetFilteredEntries(): void {
		$filter = 'con';
		$user = $this->createMock(IUser::class);
		$entries = $this->generateTestEntries();
		$provider = $this->createMock(IProvider::class);

		$this->mocks[IConfig::class]->expects($this->exactly(2))
			->method('getSystemValueInt')
			->willReturnMap([
				['sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT, 25],
				['sharing.minSearchStringLength', 0, 0],
			]);
		$this->mocks[ContactsStore::class]->expects($this->once())
			->method('getContacts')
			->with($user, $filter)
			->willReturn($entries);
		$this->mocks[ActionProviderStore::class]->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([$provider]);
		$provider->expects($this->exactly(25))
			->method('process');
		$this->mocks[IAppManager::class]->expects($this->once())
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

	public function testGetFilteredEntriesLimit(): void {
		$filter = 'con';
		$user = $this->createMock(IUser::class);
		$entries = $this->generateTestEntries();
		$provider = $this->createMock(IProvider::class);

		$this->mocks[IConfig::class]->expects($this->exactly(2))
			->method('getSystemValueInt')
			->willReturnMap([
				['sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT, 3],
				['sharing.minSearchStringLength', 0, 0],
			]);
		$this->mocks[ContactsStore::class]->expects($this->once())
			->method('getContacts')
			->with($user, $filter)
			->willReturn($entries);
		$this->mocks[ActionProviderStore::class]->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([$provider]);
		$provider->expects($this->exactly(3))
			->method('process');
		$this->mocks[IAppManager::class]->expects($this->once())
			->method('isEnabledForUser')
			->with($this->equalTo('contacts'), $user)
			->willReturn(false);
		$expected = [
			'contacts' => array_slice($entries, 0, 3),
			'contactsAppEnabled' => false,
		];

		$data = $this->manager->getEntries($user, $filter);

		$this->assertEquals($expected, $data);
	}

	public function testGetFilteredEntriesMinSearchStringLength(): void {
		$filter = 'con';
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);

		$this->mocks[IConfig::class]->expects($this->exactly(2))
			->method('getSystemValueInt')
			->willReturnMap([
				['sharing.maxAutocompleteResults', Constants::SHARING_MAX_AUTOCOMPLETE_RESULTS_DEFAULT, 3],
				['sharing.minSearchStringLength', 0, 4],
			]);
		$this->mocks[IAppManager::class]->expects($this->once())
			->method('isEnabledForUser')
			->with($this->equalTo('contacts'), $user)
			->willReturn(false);
		$expected = [
			'contacts' => [],
			'contactsAppEnabled' => false,
		];

		$data = $this->manager->getEntries($user, $filter);

		$this->assertEquals($expected, $data);
	}

	public function testFindOne(): void {
		$shareTypeFilter = 42;
		$shareWithFilter = 'foobar';

		$user = $this->createMock(IUser::class);
		$entry = current($this->generateTestEntries());
		$provider = $this->createMock(IProvider::class);
		$this->mocks[ContactsStore::class]->expects($this->once())
			->method('findOne')
			->with($user, $shareTypeFilter, $shareWithFilter)
			->willReturn($entry);
		$this->mocks[ActionProviderStore::class]->expects($this->once())
			->method('getProviders')
			->with($user)
			->willReturn([$provider]);
		$provider->expects($this->once())
			->method('process');

		$data = $this->manager->findOne($user, $shareTypeFilter, $shareWithFilter);

		$this->assertEquals($entry, $data);
	}

	public function testFindOne404(): void {
		$shareTypeFilter = 42;
		$shareWithFilter = 'foobar';

		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$this->mocks[ContactsStore::class]->expects($this->once())
			->method('findOne')
			->with($user, $shareTypeFilter, $shareWithFilter)
			->willReturn(null);
		$this->mocks[ActionProviderStore::class]->expects($this->never())
			->method('getProviders')
			->with($user)
			->willReturn([$provider]);
		$provider->expects($this->never())
			->method('process');

		$data = $this->manager->findOne($user, $shareTypeFilter, $shareWithFilter);

		$this->assertEquals(null, $data);
	}
}
