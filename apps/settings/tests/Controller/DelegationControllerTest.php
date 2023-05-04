<?php
/**
 * @copyright Copyright (c) 2021 Nextcloud GmbH
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Settings\Tests\Controller\Admin;

use OC\Settings\AuthorizedGroup;
use OCA\Settings\Controller\AuthorizedGroupController;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\IRequest;
use Test\TestCase;

class DelegationControllerTest extends TestCase {

	/** @var AuthorizedGroupService */
	private $service;

	/** @var IRequest */
	private $request;

	/** @var AuthorizedGroupController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->getMockBuilder(IRequest::class)->getMock();
		$this->service = $this->getMockBuilder(AuthorizedGroupService::class)->disableOriginalConstructor()->getMock();
		$this->controller = new AuthorizedGroupController(
			'settings', $this->request, $this->service
		);
	}

	public function testSaveSettings() {
		$setting = 'MySecretSetting';
		$oldGroups = [];
		$oldGroups[] = AuthorizedGroup::fromParams(['groupId' => 'hello', 'class' => $setting]);
		$goodbye = AuthorizedGroup::fromParams(['groupId' => 'goodbye', 'class' => $setting, 'id' => 42]);
		$oldGroups[] = $goodbye;
		$this->service->expects($this->once())
			->method('findExistingGroupsForClass')
			->with('MySecretSetting')
			->will($this->returnValue($oldGroups));

		$this->service->expects($this->once())
			->method('delete')
			->with(42);

		$this->service->expects($this->once())
			->method('create')
			->with('world', 'MySecretSetting')
			->will($this->returnValue(AuthorizedGroup::fromParams(['groupId' => 'world', 'class' => $setting])));

		$result = $this->controller->saveSettings([['gid' => 'hello'], ['gid' => 'world']], 'MySecretSetting');

		$this->assertEquals(['valid' => true], $result->getData());
	}
}
