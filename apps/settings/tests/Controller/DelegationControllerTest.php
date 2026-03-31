<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Controller\Admin;

use OCA\Settings\Controller\AuthorizedGroupController;
use OCA\Settings\Service\AuthorizedGroupService;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DelegationControllerTest extends TestCase {
	private AuthorizedGroupService&MockObject $service;
	private IRequest&MockObject $request;
	private AuthorizedGroupController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(AuthorizedGroupService::class);
		$this->controller = new AuthorizedGroupController(
			'settings', $this->request, $this->service
		);
	}

	public function testSaveSettings(): void {
		$newGroups = [['gid' => 'hello'], ['gid' => 'world']];

		// The controller delegates the entire diff-and-apply to the service.
		$this->service->expects($this->once())
			->method('saveSettings')
			->with($newGroups, 'MySecretSetting');

		$result = $this->controller->saveSettings($newGroups, 'MySecretSetting');

		$this->assertEquals(['valid' => true], $result->getData());
	}
}
