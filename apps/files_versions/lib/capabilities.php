<?php
/**
 * Copyright (c) 2013 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
class OC_Files_Versions_Capabilities {
	
	public static function getCapabilities() {
		return OC_OCS_Result(array(
			'capabilities' => array(
				'files_versions' => array(
					'versioning' => true,
					),
				),
			));
	}
	
}
?>