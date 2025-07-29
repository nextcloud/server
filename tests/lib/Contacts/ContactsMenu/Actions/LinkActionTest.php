<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Contacts\ContactsMenu\Actions;

use OC\Contacts\ContactsMenu\Actions\LinkAction;
use Test\TestCase;

class LinkActionTest extends TestCase {
	private LinkAction $action;

	protected function setUp(): void {
		parent::setUp();

		$this->action = new LinkAction();
	}

	public function testSetIcon(): void {
		$icon = 'icon-test';

		$this->action->setIcon($icon);
		$json = $this->action->jsonSerialize();

		$this->assertArrayHasKey('icon', $json);
		$this->assertEquals($json['icon'], $icon);
	}

	public function testGetSetName(): void {
		$name = 'Jane Doe';

		$this->assertEmpty($this->action->getName());
		$this->action->setName($name);
		$this->assertEquals($name, $this->action->getName());
	}

	public function testGetSetPriority(): void {
		$prio = 50;

		$this->assertEquals(10, $this->action->getPriority());
		$this->action->setPriority($prio);
		$this->assertEquals($prio, $this->action->getPriority());
	}

	public function testSetHref(): void {
		$this->action->setHref('/some/url');

		$json = $this->action->jsonSerialize();
		$this->assertArrayHasKey('hyperlink', $json);
		$this->assertEquals('/some/url', $json['hyperlink']);
	}

	public function testJsonSerialize(): void {
		$this->action->setIcon('icon-contacts');
		$this->action->setName('Nickie Works');
		$this->action->setPriority(33);
		$this->action->setHref('example.com');
		$this->action->setAppId('contacts');
		$expected = [
			'title' => 'Nickie Works',
			'icon' => 'icon-contacts',
			'hyperlink' => 'example.com',
			'appId' => 'contacts',
		];

		$json = $this->action->jsonSerialize();

		$this->assertEquals($expected, $json);
	}

	public function testJsonSerializeNoAppName(): void {
		$this->action->setIcon('icon-contacts');
		$this->action->setName('Nickie Works');
		$this->action->setPriority(33);
		$this->action->setHref('example.com');
		$expected = [
			'title' => 'Nickie Works',
			'icon' => 'icon-contacts',
			'hyperlink' => 'example.com',
			'appId' => '',
		];

		$json = $this->action->jsonSerialize();

		$this->assertEquals($expected, $json);
	}
}
