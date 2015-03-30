<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use \OCP\IConfig;

/**
 * Class Capabilities
 *
 * @package OCA\Files_Sharing
 */
class Capabilities {

	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return \OC_OCS_Result
	 */
	public static function getCapabilities() {
		$config = \OC::$server->getConfig();
		$cap = new Capabilities($config);
		return $cap->getCaps();
	}


	/**
	 * @return \OC_OCS_Result
	 */
	public function getCaps() {
		$res = [];

		$public = [];
		$public['enabled'] = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
		if ($public['enabled']) {
			$public['password'] = [];
			$public['password']['enforced'] = ($this->config->getAppValue('core', 'shareapi_enforce_links_password', 'yes') === 'yes');

			$public['expire_date'] = [];
			$public['expire_date']['enabled'] = $this->config->getAppValue('core', 'shareapi_default_expire_date', 'yes') === 'yes';
			if ($public['expire_date']['enabled']) {
				$public['expire_date']['days'] = $this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
				$public['expire_date']['enforced'] = $this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'yes') === 'yes';
			}

			$public['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_public_notification', 'yes') === 'yes';
		}
		$res["public"] = $public;

		$res['user']['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_mail_notification', 'yes') === 'yes';

		$res['resharing'] = $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes') === 'yes';


		return new \OC_OCS_Result([
			'capabilities' => [
				'files_sharing' => $res
				],
			]);
	}
	
}
