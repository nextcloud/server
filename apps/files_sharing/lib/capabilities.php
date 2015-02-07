<?php
/**
 * Copyright (c) Roeland Jago Douma <roeland@famdouma.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
		$res = array();

		$public = false;
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes') {
			$public = array();
			$public['password_enforced'] = ($this->config->getAppValue('core', 'shareapi_enforce_links_password', 'yes') === 'yes');

			$public['expire_date'] = false;
			if ($this->config->getAppValue('core', 'shareapi_default_expire_date', 'yes') === 'yes') {
				$public['expire_date'] = array();
				$public['expire_date']['days'] = $this->config->getAppValue('core', 'shareapi_expire_after_n_days', '7');
				$public['expire_date']['enforce'] = $this->config->getAppValue('core', 'shareapi_enforce_expire_date', 'yes') === 'yes';
			}

			$public['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_public_notification', 'yes') === 'yes';
		}
		$res["public"] = $public;

		$res['user']['send_mail'] = $this->config->getAppValue('core', 'shareapi_allow_mail_notification', 'yes') === 'yes';

		$res['resharing'] = $this->config->getAppValue('core', 'shareapi_allow_resharing', 'yes') === 'yes';


		return new \OC_OCS_Result(array(
			'capabilities' => array(
				'files_sharing' => $res
				),
			));
	}
	
}
