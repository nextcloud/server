<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sascha Wiswedel <sascha.wiswedel@nextcloud.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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
namespace OCA\Settings\Settings\Admin;

use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Constants;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IDelegatedSettings;
use OCP\Share\IManager;
use OCP\Util;

class Sharing implements IDelegatedSettings {
	public function __construct(
		private IConfig $config,
		private IL10N $l,
		private IManager $shareManager,
		private IAppManager $appManager,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialState,
		private string $appName,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$excludedGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
		$linksExcludedGroups = $this->config->getAppValue('core', 'shareapi_allow_links_exclude_groups', '');
		$excludedPasswordGroups = $this->config->getAppValue('core', 'shareapi_enforce_links_password_excluded_groups', '');

		$parameters = [
			// Built-In Sharing
			'enabled' => $this->getHumanBooleanConfig('core', 'shareapi_enabled', true),
			'allowGroupSharing' => $this->getHumanBooleanConfig('core', 'shareapi_allow_group_sharing', true),
			'allowLinks' => $this->getHumanBooleanConfig('core', 'shareapi_allow_links', true),
			'allowLinksExcludeGroups' => json_decode($linksExcludedGroups, true) ?? [],
			'allowPublicUpload' => $this->getHumanBooleanConfig('core', 'shareapi_allow_public_upload', true),
			'allowResharing' => $this->getHumanBooleanConfig('core', 'shareapi_allow_resharing', true),
			'allowShareDialogUserEnumeration' => $this->getHumanBooleanConfig('core', 'shareapi_allow_share_dialog_user_enumeration', true),
			'restrictUserEnumerationToGroup' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_to_group'),
			'restrictUserEnumerationToPhone' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_to_phone'),
			'restrictUserEnumerationFullMatch' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_full_match', true),
			'restrictUserEnumerationFullMatchUserId' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_full_match_userid', true),
			'restrictUserEnumerationFullMatchEmail' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_full_match_email', true),
			'restrictUserEnumerationFullMatchIgnoreSecondDN' => $this->getHumanBooleanConfig('core', 'shareapi_restrict_user_enumeration_full_match_ignore_second_dn'),
			'enforceLinksPassword' => Util::isPublicLinkPasswordRequired(false),
			'enforceLinksPasswordExcludedGroups' => json_decode($excludedPasswordGroups) ?? [],
			'enforceLinksPasswordExcludedGroupsEnabled' => $this->config->getSystemValueBool('sharing.allow_disabled_password_enforcement_groups', false),
			'onlyShareWithGroupMembers' => $this->shareManager->shareWithGroupMembersOnly(),
			'defaultExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_default_expire_date'),
			'expireAfterNDays' => $this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7'),
			'enforceExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_enforce_expire_date'),
			'excludeGroups' => $this->getHumanBooleanConfig('core', 'shareapi_exclude_groups'),
			'excludeGroupsList' => json_decode($excludedGroups, true) ?? [],
			'publicShareDisclaimerText' => $this->config->getAppValue('core', 'shareapi_public_link_disclaimertext', null),
			'enableLinkPasswordByDefault' => $this->getHumanBooleanConfig('core', 'shareapi_enable_link_password_by_default'),
			'defaultPermissions' => (int)$this->config->getAppValue('core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL),
			'defaultInternalExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_default_internal_expire_date'),
			'internalExpireAfterNDays' => $this->config->getAppValue('core', 'shareapi_internal_expire_after_n_days', '7'),
			'enforceInternalExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_enforce_internal_expire_date'),
			'defaultRemoteExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_default_remote_expire_date'),
			'remoteExpireAfterNDays' => $this->config->getAppValue('core', 'shareapi_remote_expire_after_n_days', '7'),
			'enforceRemoteExpireDate' => $this->getHumanBooleanConfig('core', 'shareapi_enforce_remote_expire_date'),
		];

		$this->initialState->provideInitialState('sharingAppEnabled', $this->appManager->isEnabledForUser('files_sharing'));
		$this->initialState->provideInitialState('sharingDocumentation', $this->urlGenerator->linkToDocs('admin-sharing'));
		$this->initialState->provideInitialState('sharingSettings', $parameters);

		\OCP\Util::addScript($this->appName, 'vue-settings-admin-sharing');
		return new TemplateResponse($this->appName, 'settings/admin/sharing', [], '');
	}

	/**
	 * Helper function to retrive boolean values from human readable strings ('yes' / 'no')
	 */
	private function getHumanBooleanConfig(string $app, string $key, bool $default = false): bool {
		return $this->config->getAppValue($app, $key, $default ? 'yes' : 'no') === 'yes';
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

	public function getAuthorizedAppConfig(): array {
		return [
			'core' => ['/shareapi_.*/'],
		];
	}

	public function getName(): ?string {
		return null;
	}
}
