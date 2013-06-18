<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
//both, libreoffice backend and php fallback, need imagick
if (extension_loaded('imagick')) {
	//let's see if there is libreoffice or openoffice on this machine
	if(shell_exec('libreoffice --headless --version') || shell_exec('openoffice --headless --version') || is_string(\OC_Config::getValue('preview_libreoffice_path', null))) {
		require_once('libreoffice-cl.php');
	}else{
		//in case there isn't, use our fallback
		require_once('msoffice.php');
	}
}