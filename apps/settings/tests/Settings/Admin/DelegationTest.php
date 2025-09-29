<?php
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Settings\Admin\Delegation;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DelegationTest extends TestCase {
	private Delegation $delegation;
	private IManager|MockObject $settingManager;
	private IInitialState|MockObject $initialStateService;
	private IGroupManager|MockObject $groupManager;
	private AuthorizedGroupService|MockObject $authorizedGroupService;
	private IURLGenerator|MockObject $urlGenerator;
	private IL10N|MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();

		$this->settingManager = $this->createMock(IManager::class);
		$this->initialStateService = $this->createMock(IInitialState::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->authorizedGroupService = $this->createMock(AuthorizedGroupService::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->delegation = new Delegation(
			$this->settingManager,
			$this->initialStateService,
			$this->groupManager,
			$this->authorizedGroupService,
			$this->urlGenerator,
			$this->l10n,
		);
	}

	public function testImplementsIDelegatedSettings(): void {
		$this->assertInstanceOf(IDelegatedSettings::class, $this->delegation);
	}

	public function testGetPriority(): void {
		$this->assertEquals(75, $this->delegation->getPriority());
	}

	public function testGetName(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Delegation')
			->willReturn('Delegation');

		$this->assertEquals('Delegation', $this->delegation->getName());
	}

	public function testGetAuthorizedAppConfig(): void {
		$this->assertEquals([], $this->delegation->getAuthorizedAppConfig());
	}

	public function testGetSection(): void {
		$this->assertEquals('admindelegation', $this->delegation->getSection());
	}

	public function testGetForm(): void {
		// Mock admin sections
		$this->settingManager->method('getAdminSections')
			->willReturn([]);

		// Mock group search - should filter out admin group
		$adminGroup = $this->createMock(IGroup::class);
		$adminGroup->method('getGID')->willReturn('admin');

		$userGroup = $this->createMock(IGroup::class);
		$userGroup->method('getGID')->willReturn('users');
		$userGroup->method('getDisplayName')->willReturn('Users');

		$this->groupManager->method('search')
			->with('')
			->willReturn([$adminGroup, $userGroup]);

		// Mock authorized group service
		$this->authorizedGroupService->method('findAll')
			->willReturn([]);

		// Mock URL generator for docs link
		$this->urlGenerator->method('linkToDocs')
			->with('admin-delegation')
			->willReturn('https://docs.example.com/admin-delegation');

		// Expect 4 calls to provideInitialState:
		// 1. available-settings
		// 2. available-groups
		// 3. authorized-groups
		// 4. authorized-settings-doc-link
		$this->initialStateService->expects($this->exactly(4))
			->method('provideInitialState')
			->withConsecutive(
				['available-settings', []],
				['available-groups', [['displayName' => 'Users', 'gid' => 'users']]],
				['authorized-groups', []],
				['authorized-settings-doc-link', 'https://docs.example.com/admin-delegation']
			);

		$result = $this->delegation->getForm();

		$this->assertInstanceOf(TemplateResponse::class, $result);
		$this->assertEquals('settings/admin/delegation', $result->getTemplateName());
	}

	public function testGetFormFiltersAdminGroup(): void {
		// Test that admin group is filtered out from available groups
		$this->settingManager->method('getAdminSections')->willReturn([]);

		$adminGroup = $this->createMock(IGroup::class);
		$adminGroup->method('getGID')->willReturn('admin');

		$testGroup = $this->createMock(IGroup::class);
		$testGroup->method('getGID')->willReturn('testgroup');
		$testGroup->method('getDisplayName')->willReturn('Test Group');

		$this->groupManager->method('search')
			->willReturn([$adminGroup, $testGroup]);

		$this->authorizedGroupService->method('findAll')->willReturn([]);
		$this->urlGenerator->method('linkToDocs')->willReturn('');

		// Admin group should be filtered out, only testgroup should remain
		$this->initialStateService->expects($this->exactly(4))
			->method('provideInitialState')
			->withConsecutive(
				['available-settings', []],
				['available-groups', [['displayName' => 'Test Group', 'gid' => 'testgroup']]],
				['authorized-groups', []],
				['authorized-settings-doc-link', '']
			);

		$this->delegation->getForm();
	}
}
