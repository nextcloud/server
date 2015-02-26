<?php
/**
 * Copyright (c) Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
namespace OCA\Files_Trashbin;


/**
 * Class Capabilities
 *
 * @package OCA\Files_Trashbin
 */
class Capabilities {

	/**
	 * @return \OC_OCS_Result
	 */
	public static function getCapabilities() {
		return new \OC_OCS_Result(array(
			'capabilities' => array(
				'files' => array(
					'undelete' => true,
					),
				),
			));
	}
	
}
