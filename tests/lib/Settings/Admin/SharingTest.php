<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\Settings\Admin;

use OC\Settings\Admin\Sharing;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use Test\TestCase;

class SharingTest extends TestCase {
	/** @var Sharing */
	private $admin;
	/** @var IConfig */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();

		$this->admin = new Sharing(
			$this->config
		);
	}

	public function testGetFormWithoutExcludedGroups() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'shareapi_exclude_groups_list', '')
			->willReturn('');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'shareapi_allow_group_sharing', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('core', 'shareapi_allow_mail_notification', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(4))
			->method('getAppValue')
			->with('core', 'shareapi_allow_public_notification', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(5))
			->method('getAppValue')
			->with('core', 'shareapi_allow_public_upload', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(6))
			->method('getAppValue')
			->with('core', 'shareapi_allow_resharing', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(7))
			->method('getAppValue')
			->with('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(8))
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(9))
			->method('getAppValue')
			->with('core', 'shareapi_default_expire_date', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(10))
			->method('getAppValue')
			->with('core', 'shareapi_expire_after_n_days', '7')
			->willReturn('7');
		$this->config
			->expects($this->at(11))
			->method('getAppValue')
			->with('core', 'shareapi_enforce_expire_date', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(12))
			->method('getAppValue')
			->with('core', 'shareapi_exclude_groups', 'no')
			->willReturn('no');

		$expected = new TemplateResponse(
			'settings',
			'admin/sharing',
			[
				'allowGroupSharing'               => 'yes',
				'allowLinks'                      => 'yes',
				'allowMailNotification'           => 'no',
				'allowPublicMailNotification'     => 'no',
				'allowPublicUpload'               => 'yes',
				'allowResharing'                  => 'yes',
				'allowShareDialogUserEnumeration' => 'yes',
				'enforceLinkPassword'             => false,
				'onlyShareWithGroupMembers'       => false,
				'shareAPIEnabled'                 => 'yes',
				'shareDefaultExpireDateSet'       => 'no',
				'shareExpireAfterNDays'           => '7',
				'shareEnforceExpireDate'          => 'no',
				'shareExcludeGroups'              => false,
				'shareExcludedGroupsList'         => '',
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithExcludedGroups() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'shareapi_exclude_groups_list', '')
			->willReturn('["NoSharers","OtherNoSharers"]');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'shareapi_allow_group_sharing', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(3))
			->method('getAppValue')
			->with('core', 'shareapi_allow_mail_notification', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(4))
			->method('getAppValue')
			->with('core', 'shareapi_allow_public_notification', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(5))
			->method('getAppValue')
			->with('core', 'shareapi_allow_public_upload', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(6))
			->method('getAppValue')
			->with('core', 'shareapi_allow_resharing', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(7))
			->method('getAppValue')
			->with('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(8))
			->method('getAppValue')
			->with('core', 'shareapi_enabled', 'yes')
			->willReturn('yes');
		$this->config
			->expects($this->at(9))
			->method('getAppValue')
			->with('core', 'shareapi_default_expire_date', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(10))
			->method('getAppValue')
			->with('core', 'shareapi_expire_after_n_days', '7')
			->willReturn('7');
		$this->config
			->expects($this->at(11))
			->method('getAppValue')
			->with('core', 'shareapi_enforce_expire_date', 'no')
			->willReturn('no');
		$this->config
			->expects($this->at(12))
			->method('getAppValue')
			->with('core', 'shareapi_exclude_groups', 'no')
			->willReturn('yes');

		$expected = new TemplateResponse(
			'settings',
			'admin/sharing',
			[
				'allowGroupSharing'               => 'yes',
				'allowLinks'                      => 'yes',
				'allowMailNotification'           => 'no',
				'allowPublicMailNotification'     => 'no',
				'allowPublicUpload'               => 'yes',
				'allowResharing'                  => 'yes',
				'allowShareDialogUserEnumeration' => 'yes',
				'enforceLinkPassword'             => false,
				'onlyShareWithGroupMembers'       => false,
				'shareAPIEnabled'                 => 'yes',
				'shareDefaultExpireDateSet'       => 'no',
				'shareExpireAfterNDays'           => '7',
				'shareEnforceExpireDate'          => 'no',
				'shareExcludeGroups'              => true,
				'shareExcludedGroupsList'         => 'NoSharers|OtherNoSharers',
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('sharing', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
