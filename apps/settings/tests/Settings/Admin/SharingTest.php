<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Settings\Admin\Sharing;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Constants;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SharingTest extends TestCase {
	/** @var Sharing */
	private $admin;
	/** @var IConfig&MockObject */
	private $config;
	/** @var IL10N&MockObject */
	private $l10n;
	/** @var IManager|MockObject */
	private $shareManager;
	/** @var IAppManager|MockObject */
	private $appManager;
	/** @var IURLGenerator|MockObject */
	private $urlGenerator;
	/** @var IInitialState|MockObject */
	private $initialState;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		/** @var IManager|MockObject */
		$this->shareManager = $this->getMockBuilder(IManager::class)->getMock();
		/** @var IAppManager|MockObject */
		$this->appManager = $this->getMockBuilder(IAppManager::class)->getMock();
		/** @var IURLGenerator|MockObject */
		$this->urlGenerator = $this->getMockBuilder(IURLGenerator::class)->getMock();
		/** @var IInitialState|MockObject */
		$this->initialState = $this->getMockBuilder(IInitialState::class)->getMock();

		$this->admin = new Sharing(
			$this->config,
			$this->l10n,
			$this->shareManager,
			$this->appManager,
			$this->urlGenerator,
			$this->initialState,
			'settings',
		);
	}

	public function testGetFormWithoutExcludedGroups(): void {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_exclude_groups_list', '', ''],
				['core', 'shareapi_allow_links_exclude_groups', '', ''],
				['core', 'shareapi_allow_group_sharing', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
				['core', 'shareapi_allow_public_upload', 'yes', 'yes'],
				['core', 'shareapi_allow_resharing', 'yes', 'yes'],
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_userid', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', 'no', 'no'],
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_default_expire_date', 'no', 'no'],
				['core', 'shareapi_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_expire_date', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_public_link_disclaimertext', '', 'Lorem ipsum'],
				['core', 'shareapi_enable_link_password_by_default', 'no', 'yes'],
				['core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL, Constants::PERMISSION_ALL],
				['core', 'shareapi_default_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_internal_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_default_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_remote_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
				['core', 'shareapi_only_share_with_group_members_exclude_group_list', '', '[]'],
			]);
		$this->shareManager->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->appManager->method('isEnabledForUser')->with('files_sharing')->willReturn(false);

		$initialStateCalls = [];
		$this->initialState
			->expects($this->exactly(3))
			->method('provideInitialState')
			->willReturnCallback(function (string $key) use (&$initialStateCalls) {
				$initialStateCalls[$key] = func_get_args();
			});
		
		$expectedInitialStateCalls = [
			'sharingAppEnabled' => false,
			'sharingDocumentation' => '',
			'sharingSettings' => [
				'allowGroupSharing' => true,
				'allowLinks' => true,
				'allowPublicUpload' => true,
				'allowResharing' => true,
				'allowShareDialogUserEnumeration' => true,
				'restrictUserEnumerationToGroup' => false,
				'restrictUserEnumerationToPhone' => false,
				'restrictUserEnumerationFullMatch' => true,
				'restrictUserEnumerationFullMatchUserId' => true,
				'restrictUserEnumerationFullMatchEmail' => true,
				'restrictUserEnumerationFullMatchIgnoreSecondDN' => false,
				'enforceLinksPassword' => false,
				'onlyShareWithGroupMembers' => false,
				'enabled' => true,
				'defaultExpireDate' => false,
				'expireAfterNDays' => '7',
				'enforceExpireDate' => false,
				'excludeGroups' => 'no',
				'excludeGroupsList' => [],
				'publicShareDisclaimerText' => 'Lorem ipsum',
				'enableLinkPasswordByDefault' => true,
				'defaultPermissions' => Constants::PERMISSION_ALL,
				'defaultInternalExpireDate' => false,
				'internalExpireAfterNDays' => '7',
				'enforceInternalExpireDate' => false,
				'defaultRemoteExpireDate' => false,
				'remoteExpireAfterNDays' => '7',
				'enforceRemoteExpireDate' => false,
				'allowLinksExcludeGroups' => [],
				'onlyShareWithGroupMembersExcludeGroupList' => [],
				'enforceLinksPasswordExcludedGroups' => [],
				'enforceLinksPasswordExcludedGroupsEnabled' => false,
			]
		];

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/sharing',
			[],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
		$this->assertEquals(sort($expectedInitialStateCalls), sort($initialStateCalls), 'Provided initial state does not match');
	}

	public function testGetFormWithExcludedGroups(): void {
		$this->config
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_exclude_groups_list', '', '["NoSharers","OtherNoSharers"]'],
				['core', 'shareapi_allow_links_exclude_groups', '', ''],
				['core', 'shareapi_allow_group_sharing', 'yes', 'yes'],
				['core', 'shareapi_allow_links', 'yes', 'yes'],
				['core', 'shareapi_allow_public_upload', 'yes', 'yes'],
				['core', 'shareapi_allow_resharing', 'yes', 'yes'],
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_phone', 'no', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_userid', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn', 'no', 'no'],
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_default_expire_date', 'no', 'no'],
				['core', 'shareapi_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_expire_date', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'yes'],
				['core', 'shareapi_public_link_disclaimertext', '', 'Lorem ipsum'],
				['core', 'shareapi_enable_link_password_by_default', 'no', 'yes'],
				['core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL, Constants::PERMISSION_ALL],
				['core', 'shareapi_default_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_internal_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_default_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_remote_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_enforce_links_password_excluded_groups', '', ''],
				['core', 'shareapi_only_share_with_group_members_exclude_group_list', '', '[]'],
			]);
		$this->shareManager->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->appManager->method('isEnabledForUser')->with('files_sharing')->willReturn(true);

		$initialStateCalls = [];
		$this->initialState
			->expects($this->exactly(3))
			->method('provideInitialState')
			->willReturnCallback(function (string $key) use (&$initialStateCalls) {
				$initialStateCalls[$key] = func_get_args();
			});

		$expectedInitialStateCalls = [
			'sharingAppEnabled' => true,
			'sharingDocumentation' => '',
			'sharingSettings' => [
				'allowGroupSharing' => true,
				'allowLinks' => true,
				'allowPublicUpload' => true,
				'allowResharing' => true,
				'allowShareDialogUserEnumeration' => true,
				'restrictUserEnumerationToGroup' => false,
				'restrictUserEnumerationToPhone' => false,
				'restrictUserEnumerationFullMatch' => true,
				'restrictUserEnumerationFullMatchUserId' => true,
				'restrictUserEnumerationFullMatchEmail' => true,
				'restrictUserEnumerationFullMatchIgnoreSecondDN' => false,
				'enforceLinksPassword' => false,
				'onlyShareWithGroupMembers' => false,
				'enabled' => true,
				'defaultExpireDate' => false,
				'expireAfterNDays' => '7',
				'enforceExpireDate' => false,
				'excludeGroups' => 'yes',
				'excludeGroupsList' => ['NoSharers','OtherNoSharers'],
				'publicShareDisclaimerText' => 'Lorem ipsum',
				'enableLinkPasswordByDefault' => true,
				'defaultPermissions' => Constants::PERMISSION_ALL,
				'defaultInternalExpireDate' => false,
				'internalExpireAfterNDays' => '7',
				'enforceInternalExpireDate' => false,
				'defaultRemoteExpireDate' => false,
				'remoteExpireAfterNDays' => '7',
				'enforceRemoteExpireDate' => false,
				'allowLinksExcludeGroups' => [],
				'onlyShareWithGroupMembersExcludeGroupList' => [],
				'enforceLinksPasswordExcludedGroups' => [],
				'enforceLinksPasswordExcludedGroupsEnabled' => false,
			],
		];

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/sharing',
			[],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
		$this->assertEquals(sort($expectedInitialStateCalls), sort($initialStateCalls), 'Provided initial state does not match');
	}

	public function testGetSection(): void {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
