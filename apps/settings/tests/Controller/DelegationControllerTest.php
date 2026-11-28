<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller\Admin;

use OC\Settings\AuthorizedGroup;
use OCA\Settings\Controller\AuthorizedGroupController;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DelegationControllerTest extends TestCase {
	private AuthorizedGroupService&MockObject $service;
	private IRequest&MockObject $request;
	private IEventDispatcher&MockObject $eventDispatcher;
	private AuthorizedGroupController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(AuthorizedGroupService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->controller = new AuthorizedGroupController(
			'settings', $this->request, $this->service, $this->eventDispatcher
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
			->willReturn($oldGroups);

		$this->service->expects($this->once())
			->method('delete')
			->with(42);

		$this->service->expects($this->once())
			->method('create')
			->with('world', 'MySecretSetting')
			->willReturn(AuthorizedGroup::fromParams(['groupId' => 'world', 'class' => $setting]));

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'settings.delegation.updated'
				&& $event->getParameters() === ['settingClass' => 'MySecretSetting', 'addedGroups' => 'world', 'removedGroups' => 'goodbye']
				&& $event->getLogMessage() === 'Admin delegation for setting "%s" was changed: added groups "%s", removed groups "%s"'));

		$result = $this->controller->saveSettings([['gid' => 'hello'], ['gid' => 'world']], 'MySecretSetting');

		$this->assertEquals(['valid' => true], $result->getData());
	}

	public function testSaveSettingsWithoutChangesIsNotAudited(): void {
		$this->service->method('findExistingGroupsForClass')
			->willReturn([AuthorizedGroup::fromParams(['groupId' => 'hello', 'class' => 'MySecretSetting'])]);

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');

		$this->controller->saveSettings([['gid' => 'hello']], 'MySecretSetting');
	}
}
