<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

abstract class Office extends Provider {
	private $cmd;

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$this->initCmd();
		if(is_null($this->cmd)) {
			return false;
		}

		$absPath = $fileview->toTmpFile($path);

		$tmpDir = get_temp_dir();

		$defaultParameters = ' -env:UserInstallation=file://' . escapeshellarg($tmpDir . '/owncloud-' . \OC_Util::getInstanceId().'/') . ' --headless --nologo --nofirststartwizard --invisible --norestore --convert-to pdf --outdir ';
		$clParameters = \OCP\Config::getSystemValue('preview_office_cl_parameters', $defaultParameters);

		$exec = $this->cmd . $clParameters . escapeshellarg($tmpDir) . ' ' . escapeshellarg($absPath);

		shell_exec($exec);

		//create imagick object from pdf
		try{
			$pdf = new \imagick($absPath . '.pdf' . '[0]');
			$pdf->setImageFormat('jpg');
		} catch (\Exception $e) {
			unlink($absPath);
			unlink($absPath . '.pdf');
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			return false;
		}

		$image = new \OC_Image();
		$image->loadFromData($pdf);

		unlink($absPath);
		unlink($absPath . '.pdf');

		return $image->valid() ? $image : false;
	}

	private function initCmd() {
		$cmd = '';

		if(is_string(\OC_Config::getValue('preview_libreoffice_path', null))) {
			$cmd = \OC_Config::getValue('preview_libreoffice_path', null);
		}

		$whichLibreOffice = shell_exec('command -v libreoffice');
		if($cmd === '' && !empty($whichLibreOffice)) {
			$cmd = 'libreoffice';
		}

		$whichOpenOffice = shell_exec('command -v openoffice');
		if($cmd === '' && !empty($whichOpenOffice)) {
			$cmd = 'openoffice';
		}

		if($cmd === '') {
			$cmd = null;
		}

		$this->cmd = $cmd;
	}
}
