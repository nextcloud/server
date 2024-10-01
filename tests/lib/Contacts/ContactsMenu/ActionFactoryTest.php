<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\ActionFactory;
use OCP\Contacts\ContactsMenu\IAction;
use Test\TestCase;

class ActionFactoryTest extends TestCase {
	private ActionFactory $actionFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->actionFactory = new ActionFactory();
	}

	public function testNewLinkAction(): void {
		$icon = 'icon-test';
		$name = 'Test';
		$href = 'some/url';

		$action = $this->actionFactory->newLinkAction($icon, $name, $href);

		$this->assertInstanceOf(IAction::class, $action);
		$this->assertEquals($name, $action->getName());
		$this->assertEquals(10, $action->getPriority());
	}

	public function testNewEMailAction(): void {
		$icon = 'icon-test';
		$name = 'Test';
		$href = 'user@example.com';

		$action = $this->actionFactory->newEMailAction($icon, $name, $href);

		$this->assertInstanceOf(IAction::class, $action);
		$this->assertEquals($name, $action->getName());
		$this->assertEquals(10, $action->getPriority());
		$this->assertEquals('mailto:user@example.com', $action->getHref());
	}
}
