<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing;

use OCP\Capabilities\ICapability;
use \OCP\IConfig;

/**
 * Class Capabilities
 *
 * @package OCA\Files_Sharing
 */
class Capabilities implements ICapability {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * Return this classes capabilities
	 *
	 * @return array
	 */
	public function getCapabilities() {
		$res = [];

		if ($this->config->getAppValue('core', 'shareapi_enabled', 'yes') !== 'yes') {
			$res['api_enabled'] = false;
			$res['public'] = ['enabled' => false];
			$res['user'] = ['send_mail' => false];
			$res['resharing'] = false;
		} else {
			$res['api_enabled'] = true;

			$public = [];
			$public['enabled'] = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
			if ($public['enabled']) {
				$public['password'] = [];
				$public['password']['enforced'] = ($this->config->getAppValue('core', 'shareapi_enforce_links_password', 'no') === 'yes');

				$public['expire_date'] = [];
				$public['expire_date']['enabled'] = $this->config->getAppValue('core', 'shareapi_default_expire_date', 'no') === 'yes';
				if ($public['expire_date']['enabled']) {
					$public['expire_date']['days'] = $this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
					$public['expire_date']['enforced'] = $this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'no') === 'yes';
				}

				$public['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_public_notification', 'no') === 'yes';
				$public['upload'] = $this->config->getAppValue('core', 'shareapi_allow_public_upload', 'yes') === 'yes';
				$public['upload_files_drop'] = $public['upload'];
			}
			$res["public"] = $public;

			$res['user']['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_mail_notification', 'no') === 'yes';

			$res['resharing'] = $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes') === 'yes';

			$res['group_sharing'] = $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes') === 'yes';
		}

		//Federated sharing
		$res['federation'] = [
			'outgoing'  => $this->config->getAppValue('files_sharing', 'outgoing_server2server_share_enabled', 'yes') === 'yes',
			'incoming' => $this->config->getAppValue('files_sharing', 'incoming_server2server_share_enabled', 'yes') === 'yes'
		];

		return [
			'files_sharing' => $res,
		];
	}
}
