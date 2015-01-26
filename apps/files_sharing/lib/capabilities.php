<?php
/**
 * Copyright (c) Roeland Jago Douma <roeland@famdouma.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
namespace OCA\Files_Sharing;


/**
 * Class Capabilities
 *
 * @package OCA\Files_Sharing
 */
class Capabilities {

	/**
	 * @return \OC_OCS_Result
	 */
	public static function getCapabilities() {
		$config = \OC::$server->getConfig();

		$res = array();
		if ($config->getAppValue('core', 'shareapi_allow_links', 'yes') === "yes") {
			$res["allow_links"] = 1;

			if ($config->getAppValue('core', 'shareapi_enforce_links_password', 'yes') === "yes") {
				$res["enforce_links_password"] = 1;
			} else {
				$res["enforce_links_password"] = 0;
			}

			if ($config->getAppValue('core', 'shareapi_allow_public_upload', 'yes') === "yes") {
				$res["allow_public_upload"] = 1;
			} else {
				$res["allow_public_upload"] = 0;
			}
		} else {
			$res["allow_links"] = 0;
		}
		
		return new \OC_OCS_Result(array(
			'capabilities' => array(
				'files' => array(
					'sharing' => $res
					),
				),
			));
	}
	
}
