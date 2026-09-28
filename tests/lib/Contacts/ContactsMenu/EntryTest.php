<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\Actions\LinkAction;
use OC\Contacts\ContactsMenu\Entry;
use Test\TestCase;

class EntryTest extends TestCase {
	private Entry $entry;

	protected function setUp(): void {
		parent::setUp();

		$this->entry = new Entry();
	}

	public function testSetId(): void {
		$this->entry->setId(123);
		$this->addToAssertionCount(1);
	}

	public function testSetGetFullName(): void {
		$fn = 'Danette Chaille';
		$this->assertEquals('', $this->entry->getFullName());
		$this->entry->setFullName($fn);
		$this->assertEquals($fn, $this->entry->getFullName());
	}

	public function testAddGetEMailAddresses(): void {
		$this->assertEmpty($this->entry->getEMailAddresses());
		$this->entry->addEMailAddress('user@example.com');
		$this->assertEquals(['user@example.com'], $this->entry->getEMailAddresses());
	}

	public function testAddAndSortAction(): void {
		// Three actions, two with equal priority
		$action1 = new LinkAction();
		$action2 = new LinkAction();
		$action3 = new LinkAction();
		$action1->setPriority(10);
		$action1->setName('Bravo');

		$action2->setPriority(0);
		$action2->setName('Batman');

		$action3->setPriority(10);
		$action3->setName('Alfa');

		$this->entry->addAction($action1);
		$this->entry->addAction($action2);
		$this->entry->addAction($action3);
		$sorted = $this->entry->getActions();

		$this->assertSame($action3, $sorted[0]);
		$this->assertSame($action1, $sorted[1]);
		$this->assertSame($action2, $sorted[2]);
	}

	public function testSetGetProperties(): void {
		$props = [
			'prop1' => 123,
			'prop2' => 'string',
		];

		$this->entry->setProperties($props);

		$this->assertNull($this->entry->getProperty('doesntexist'));
		$this->assertEquals(123, $this->entry->getProperty('prop1'));
		$this->assertEquals('string', $this->entry->getProperty('prop2'));
	}

	public function testJsonSerialize(): void {
		$expectedJson = [
			'id' => '123',
			'fullName' => 'Guadalupe Frisbey',
			'topAction' => null,
			'actions' => [],
			'lastMessage' => '',
			'avatar' => null,
			'emailAddresses' => ['user@example.com'],
			'profileTitle' => null,
			'profileUrl' => null,
			'status' => null,
			'statusMessage' => null,
			'statusMessageTimestamp' => null,
			'statusIcon' => null,
			'isUser' => false,
			'uid' => null,
		];

		$this->entry->setId(123);
		$this->entry->setFullName('Guadalupe Frisbey');
		$this->entry->addEMailAddress('user@example.com');
		$json = $this->entry->jsonSerialize();

		$this->assertEquals($expectedJson, $json);
	}
}
