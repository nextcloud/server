<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OC\Core\AppInfo\ConfigLexicon;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\Constants;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Share\IManager;

/**
 * Class Capabilities
 *
 * @package OCA\Files_Sharing
 */
class Capabilities implements ICapability {
	public function __construct(
		private IConfig $config,
		private readonly IAppConfig $appConfig,
		private IManager $shareManager,
		private IAppManager $appManager,
	) {
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array{
	 *     files_sharing: array{
	 *         api_enabled: bool,
	 *         public: array{
	 *             enabled: bool,
	 *             password?: array{
	 *                 enforced: bool,
	 *                 askForOptionalPassword: bool
	 *             },
	 *     		   multiple_links?: bool,
	 *             expire_date?: array{
	 *                 enabled: bool,
	 *                 days?: int,
	 *                 enforced?: bool,
	 *             },
	 *             expire_date_internal?: array{
	 *                 enabled: bool,
	 *                 days?: int,
	 *                 enforced?: bool,
	 *             },
	 *             expire_date_remote?: array{
	 *                 enabled: bool,
	 *                 days?: int,
	 *                 enforced?: bool,
	 *             },
	 *             send_mail?: bool,
	 *             upload?: bool,
	 *             upload_files_drop?: bool,
	 *             custom_tokens?: bool,
	 *         },
	 *         user: array{
	 *             send_mail: bool,
	 *             expire_date?: array{
	 *                 enabled: bool,
	 *             },
	 *         },
	 *         resharing: bool,
	 *         group_sharing?: bool,
	 *         group?: array{
	 *             enabled: bool,
	 *             expire_date?: array{
	 *                 enabled: bool,
	 *             },
	 *         },
	 *         default_permissions?: int,
	 *         federation: array{
	 *             outgoing: bool,
	 *             incoming: bool,
	 *             expire_date: array{
	 *                 enabled: bool,
	 *             },
	 *             expire_date_supported: array{
	 *                 enabled: bool,
	 *             },
	 *         },
	 *         sharee: array{
	 *             query_lookup_default: bool,
	 *             always_show_unique: bool,
	 *         },
	 *	   },
	 * }
	 */
	public function getCapabilities() {
		$res = [];

		if (!$this->shareManager->shareApiEnabled()) {
			$res['api_enabled'] = false;
			$res['public'] = ['enabled' => false];
			$res['user'] = ['send_mail' => false];
			$res['resharing'] = false;
		} else {
			$res['api_enabled'] = true;

			$public = [];
			$public['enabled'] = $this->shareManager->shareApiAllowLinks();
			if ($public['enabled']) {
				$public['password'] = [];
				$public['password']['enforced'] = $this->shareManager->shareApiLinkEnforcePassword();

				if ($public['password']['enforced']) {
					$public['password']['askForOptionalPassword'] = false;
				} else {
					$public['password']['askForOptionalPassword'] = $this->appConfig->getValueBool('core', ConfigLexicon::SHARE_LINK_PASSWORD_DEFAULT);
				}

				$public['expire_date'] = [];
				$public['multiple_links'] = true;
				$public['expire_date']['enabled'] = $this->shareManager->shareApiLinkDefaultExpireDate();
				if ($public['expire_date']['enabled']) {
					$public['expire_date']['days'] = $this->shareManager->shareApiLinkDefaultExpireDays();
					$public['expire_date']['enforced'] = $this->shareManager->shareApiLinkDefaultExpireDateEnforced();
				}

				$public['expire_date_internal'] = [];
				$public['expire_date_internal']['enabled'] = $this->shareManager->shareApiInternalDefaultExpireDate();
				if ($public['expire_date_internal']['enabled']) {
					$public['expire_date_internal']['days'] = $this->shareManager->shareApiInternalDefaultExpireDays();
					$public['expire_date_internal']['enforced'] = $this->shareManager->shareApiInternalDefaultExpireDateEnforced();
				}

				$public['expire_date_remote'] = [];
				$public['expire_date_remote']['enabled'] = $this->shareManager->shareApiRemoteDefaultExpireDate();
				if ($public['expire_date_remote']['enabled']) {
					$public['expire_date_remote']['days'] = $this->shareManager->shareApiRemoteDefaultExpireDays();
					$public['expire_date_remote']['enforced'] = $this->shareManager->shareApiRemoteDefaultExpireDateEnforced();
				}

				$public['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_public_notification', 'no') === 'yes';
				$public['upload'] = $this->shareManager->shareApiLinkAllowPublicUpload();
				$public['upload_files_drop'] = $public['upload'];
				$public['custom_tokens'] = $this->shareManager->allowCustomTokens();
			}
			$res['public'] = $public;

			$res['resharing'] = $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes') === 'yes';

			$res['user']['send_mail'] = false;
			$res['user']['expire_date']['enabled'] = true;

			// deprecated in favour of 'group', but we need to keep it for now
			// in order to stay compatible with older clients
			$res['group_sharing'] = $this->shareManager->allowGroupSharing();

			$res['group'] = [];
			$res['group']['enabled'] = $this->shareManager->allowGroupSharing();
			$res['group']['expire_date']['enabled'] = true;
			$res['default_permissions'] = (int)$this->config->getAppValue('core', 'shareapi_default_permissions', (string)Constants::PERMISSION_ALL);
		}

		//Federated sharing
		if ($this->appManager->isEnabledForAnyone('federation')) {
			$res['federation'] = [
				'outgoing' => $this->shareManager->outgoingServer2ServerSharesAllowed(),
				'incoming' => $this->config->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes') === 'yes',
				// old bogus one, expire_date was not working before, keeping for compatibility
				'expire_date' => ['enabled' => true],
				// the real deal, signifies that expiration date can be set on federated shares
				'expire_date_supported' => ['enabled' => true],
			];
		} else {
			$res['federation'] = [
				'outgoing' => false,
				'incoming' => false,
				'expire_date' => ['enabled' => false],
				'expire_date_supported' => ['enabled' => false],
			];
		}

		// Sharee searches
		$res['sharee'] = [
			'query_lookup_default' => $this->config->getSystemValueBool('gs.enabled', false),
			'always_show_unique' => $this->config->getAppValue('files_sharing', 'always_show_unique', 'yes') === 'yes',
		];

		return [
			'files_sharing' => $res,
		];
	}
}
