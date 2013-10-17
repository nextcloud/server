<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
//both, libreoffice backend and php fallback, need imagick
if (extension_loaded('imagick')) {
	$isShellExecEnabled = !in_array('shell_exec', explode(', ', ini_get('disable_functions')));

	// LibreOffice preview is currently not supported on Windows
	if (!\OC_Util::runningOnWindows()) {
		$whichLibreOffice = ($isShellExecEnabled ? shell_exec('which libreoffice') : '');
		$isLibreOfficeAvailable = !empty($whichLibreOffice);
		$whichOpenOffice = ($isShellExecEnabled ? shell_exec('which libreoffice') : '');
		$isOpenOfficeAvailable = !empty($whichOpenOffice);
		//let's see if there is libreoffice or openoffice on this machine
		if($isShellExecEnabled && ($isLibreOfficeAvailable || $isOpenOfficeAvailable || is_string(\OC_Config::getValue('preview_libreoffice_path', null)))) {
			require_once('office-cl.php');
		}else{
			//in case there isn't, use our fallback
			require_once('office-fallback.php');
		}
	} else {
		//in case there isn't, use our fallback
		require_once('office-fallback.php');
	}
}
