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

namespace Tests\Controller;

use OC\Contacts\ContactsMenu\Manager;
use OC\Core\Controller\ContactsMenuController;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class ContactsMenuControllerTest extends TestCase {

	/** @var IRequest|PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var string */
	private $userId;

	/** @var Manager|PHPUnit_Framework_MockObject_MockObject */
	private $contactsManager;

	/** @var ContactsMenuController */
	private $controller;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userId = 'user4563';
		$this->contactsManager = $this->createMock(Manager::class);

		$this->controller = new ContactsMenuController($this->request, $this->userId, $this->contactsManager);
	}

	public function testIndex() {
		$entries = [
			$this->createMock(IEntry::class),
			$this->createMock(IEntry::class),
		];
		$this->contactsManager->expects($this->once())
			->method('getEntries')
			->with($this->equalTo($this->userId), $this->equalTo(null))
			->willReturn($entries);

		$response = $this->controller->index();

		$this->assertEquals($entries, $response);
	}

}
