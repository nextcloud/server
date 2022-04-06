<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Settings\Admin\Sharing;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Constants;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SharingTest extends TestCase {
	/** @var Sharing */
	private $admin;
	/** @var IConfig */
	private $config;
	/** @var  IL10N|MockObject */
	private $l10n;
	/** @var  IManager|MockObject */
	private $shareManager;
	/** @var IAppManager|MockObject */
	private $appManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		$this->shareManager = $this->getMockBuilder(IManager::class)->getMock();
		$this->appManager = $this->getMockBuilder(IAppManager::class)->getMock();

		$this->admin = new Sharing(
			$this->config,
			$this->l10n,
			$this->shareManager,
			$this->appManager
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
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_default_expire_date', 'no', 'no'],
				['core', 'shareapi_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_expire_date', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'no'],
				['core', 'shareapi_public_link_disclaimertext', null, 'Lorem ipsum'],
				['core', 'shareapi_enable_link_password_by_default', 'no', 'yes'],
				['core', 'shareapi_default_permissions', Constants::PERMISSION_ALL, Constants::PERMISSION_ALL],
				['core', 'shareapi_default_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_internal_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_default_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_remote_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_remote_expire_date', 'no', 'no'],
			]);
		$this->shareManager->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->appManager->method('isEnabledForUser')->with('files_sharing')->willReturn(false);

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/sharing',
			[
				'sharingAppEnabled' => false,
				'allowGroupSharing' => 'yes',
				'allowLinks' => 'yes',
				'allowPublicUpload' => 'yes',
				'allowResharing' => 'yes',
				'allowShareDialogUserEnumeration' => 'yes',
				'restrictUserEnumerationToGroup' => 'no',
				'restrictUserEnumerationToPhone' => 'no',
				'restrictUserEnumerationFullMatch' => 'yes',
				'enforceLinkPassword' => false,
				'onlyShareWithGroupMembers' => false,
				'shareAPIEnabled' => 'yes',
				'shareDefaultExpireDateSet' => 'no',
				'shareExpireAfterNDays' => '7',
				'shareEnforceExpireDate' => 'no',
				'shareExcludeGroups' => false,
				'shareExcludedGroupsList' => '',
				'publicShareDisclaimerText' => 'Lorem ipsum',
				'enableLinkPasswordByDefault' => 'yes',
				'shareApiDefaultPermissions' => Constants::PERMISSION_ALL,
				'shareApiDefaultPermissionsCheckboxes' => self::invokePrivate($this->admin, 'getSharePermissionList', []),
				'shareDefaultInternalExpireDateSet' => 'no',
				'shareInternalExpireAfterNDays' => '7',
				'shareInternalEnforceExpireDate' => 'no',
				'shareDefaultRemoteExpireDateSet' => 'no',
				'shareRemoteExpireAfterNDays' => '7',
				'shareRemoteEnforceExpireDate' => 'no',
				'allowLinksExcludeGroups' => '',
				'passwordExcludedGroups' => '',
				'passwordExcludedGroupsFeatureEnabled' => false,
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
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
				['core', 'shareapi_enabled', 'yes', 'yes'],
				['core', 'shareapi_default_expire_date', 'no', 'no'],
				['core', 'shareapi_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_expire_date', 'no', 'no'],
				['core', 'shareapi_exclude_groups', 'no', 'yes'],
				['core', 'shareapi_public_link_disclaimertext', null, 'Lorem ipsum'],
				['core', 'shareapi_enable_link_password_by_default', 'no', 'yes'],
				['core', 'shareapi_default_permissions', Constants::PERMISSION_ALL, Constants::PERMISSION_ALL],
				['core', 'shareapi_default_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_internal_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_internal_expire_date', 'no', 'no'],
				['core', 'shareapi_default_remote_expire_date', 'no', 'no'],
				['core', 'shareapi_remote_expire_after_n_days', '7', '7'],
				['core', 'shareapi_enforce_remote_expire_date', 'no', 'no'],
			]);
		$this->shareManager->method('shareWithGroupMembersOnly')
			->willReturn(false);

		$this->appManager->method('isEnabledForUser')->with('files_sharing')->willReturn(true);

		$expected = new TemplateResponse(
			'settings',
			'settings/admin/sharing',
			[
				'sharingAppEnabled' => true,
				'allowGroupSharing' => 'yes',
				'allowLinks' => 'yes',
				'allowPublicUpload' => 'yes',
				'allowResharing' => 'yes',
				'allowShareDialogUserEnumeration' => 'yes',
				'restrictUserEnumerationToGroup' => 'no',
				'restrictUserEnumerationToPhone' => 'no',
				'restrictUserEnumerationFullMatch' => 'yes',
				'enforceLinkPassword' => false,
				'onlyShareWithGroupMembers' => false,
				'shareAPIEnabled' => 'yes',
				'shareDefaultExpireDateSet' => 'no',
				'shareExpireAfterNDays' => '7',
				'shareEnforceExpireDate' => 'no',
				'shareExcludeGroups' => true,
				'shareExcludedGroupsList' => 'NoSharers|OtherNoSharers',
				'publicShareDisclaimerText' => 'Lorem ipsum',
				'enableLinkPasswordByDefault' => 'yes',
				'shareApiDefaultPermissions' => Constants::PERMISSION_ALL,
				'shareApiDefaultPermissionsCheckboxes' => self::invokePrivate($this->admin, 'getSharePermissionList', []),
				'shareDefaultInternalExpireDateSet' => 'no',
				'shareInternalExpireAfterNDays' => '7',
				'shareInternalEnforceExpireDate' => 'no',
				'shareDefaultRemoteExpireDateSet' => 'no',
				'shareRemoteExpireAfterNDays' => '7',
				'shareRemoteEnforceExpireDate' => 'no',
				'allowLinksExcludeGroups' => '',
				'passwordExcludedGroups' => '',
				'passwordExcludedGroupsFeatureEnabled' => false,
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
