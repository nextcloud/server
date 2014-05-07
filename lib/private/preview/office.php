<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
//both, libreoffice backend and php fallback, need imagick
if (extension_loaded('imagick')) {

	$checkImagick = new Imagick();

	if(count($checkImagick->queryFormats('PDF')) === 1) {
		$isShellExecEnabled = \OC_Helper::is_function_enabled('shell_exec');

		// LibreOffice preview is currently not supported on Windows
		if (!\OC_Util::runningOnWindows()) {
			$whichLibreOffice = ($isShellExecEnabled ? shell_exec('command -v libreoffice') : '');
			$isLibreOfficeAvailable = !empty($whichLibreOffice);
			$whichOpenOffice = ($isShellExecEnabled ? shell_exec('command -v libreoffice') : '');
			$isOpenOfficeAvailable = !empty($whichOpenOffice);
			//let's see if there is libreoffice or openoffice on this machine
			if($isShellExecEnabled && ($isLibreOfficeAvailable || $isOpenOfficeAvailable || is_string(\OC_Config::getValue('preview_libreoffice_path', null)))) {
				require_once('office-cl.php');
			}
		}
	}
}
