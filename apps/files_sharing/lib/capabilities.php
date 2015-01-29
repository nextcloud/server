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

	private $config;

	/*
	 * @codeCoverageIgnore
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/*
	 * @codeCoverageIgnore
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
		if ($this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes') {
			$res['allow_links'] = true;

			if ($this->config->getAppValue('core', 'shareapi_enforce_links_password', 'yes') === 'yes') {
				$res['enforce_links_password'] = true;
			} 

			if ($this->config->getAppValue('core', 'shareapi_allow_public_upload', 'yes') === 'yes') {
				$res['allow_public_upload'] = true;
			}

			$res = array('sharing' => $res);
		}

		return new \OC_OCS_Result(array(
			'capabilities' => array(
				'files' => $res
				),
			));
	}
	
}
