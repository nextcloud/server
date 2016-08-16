<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OC\Settings\Admin;

use OC\Share\Share;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Sharing implements ISettings {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$excludedGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
		$excludeGroupsList = !is_null(json_decode($excludedGroups))
			? implode('|', json_decode($excludedGroups, true)) : '';

		$parameters = [
			// Built-In Sharing
			'allowGroupSharing'               => $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes'),
			'allowLinks'                      => $this->config->getAppValue('core', 'shareapi_allow_links', 'yes'),
			'allowMailNotification'           => $this->config->getAppValue('core', 'shareapi_allow_mail_notification', 'no'),
			'allowPublicMailNotification'     => $this->config->getAppValue('core', 'shareapi_allow_public_notification', 'no'),
			'allowPublicUpload'               => $this->config->getAppValue('core', 'shareapi_allow_public_upload', 'yes'),
			'allowResharing'                  => $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes'),
			'allowShareDialogUserEnumeration' => $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes'),
			'enforceLinkPassword'             => Util::isPublicLinkPasswordRequired(),
			'onlyShareWithGroupMembers'       => Share::shareWithGroupMembersOnly(),
			'shareAPIEnabled'                 => $this->config->getAppValue('core', 'shareapi_enabled', 'yes'),
			'shareDefaultExpireDateSet'       => $this->config->getAppValue('core', 'shareapi_default_expire_date', 'no'),
			'shareExpireAfterNDays'           => $this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7'),
			'shareEnforceExpireDate'          => $this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'no'),
			'shareExcludeGroups'              => $this->config->getAppValue('core', 'shareapi_exclude_groups', 'no') === 'yes' ? true : false,
			'shareExcludedGroupsList'         => $excludeGroupsList,
		];

		return new TemplateResponse('settings', 'admin/sharing', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'sharing';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 0;
	}
}
