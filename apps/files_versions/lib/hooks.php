<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class contains all hooks.
 */
 
namespace OCA_Versions;

class Hooks {

	/**
	 * listen to write event.
	 */
	public static function write_hook( $params ) {
	
		if(\OCP\Config::getSystemValue('files_versions', Storage::DEFAULTENABLED)=='true') {
			
			$versions = new Storage( new \OC_FilesystemView('') );
			
			$path = $params[\OC_Filesystem::signal_param_path];
			
			if($path<>'') $versions->store( $path );
			
		}
	}
	
}

?>
