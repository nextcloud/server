<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function testSaveSettings(): void {
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
