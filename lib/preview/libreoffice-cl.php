<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

//we need imagick to convert 
class Office extends Provider {

	private $cmd;

	public function getMimeType() {
		return null;
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$this->initCmd();
		if(is_null($this->cmd)) {
			return false;
		}

		$abspath = $fileview->toTmpFile($path);

		$tmpdir = get_temp_dir();

		$exec = $this->cmd . ' --headless --nologo --nofirststartwizard --invisible --norestore -convert-to pdf -outdir ' . escapeshellarg($tmpdir) . ' ' . escapeshellarg($abspath);
		$export = 'export HOME=/' . $tmpdir;

		shell_exec($export . "\n" . $exec);

		//create imagick object from pdf
		try{
			$pdf = new \imagick($abspath . '.pdf' . '[0]');
			$pdf->setImageFormat('jpg');
		}catch(\Exception $e){
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			return false;
		}

		$image = new \OC_Image($pdf);

		unlink($abspath);
		unlink($abspath . '.pdf');

		return $image->valid() ? $image : false;
	}

	private function initCmd() {
		$cmd = '';

		if(is_string(\OC_Config::getValue('preview_libreoffice_path', null))) {
			$cmd = \OC_Config::getValue('preview_libreoffice_path', null);
		}

		if($cmd === '' && shell_exec('libreoffice --headless --version')) {
			$cmd = 'libreoffice';
		}

		if($cmd === '' && shell_exec('openoffice --headless --version')) {
			$cmd = 'openoffice';
		}

		if($cmd === '') {
			$cmd = null;
		}

		$this->cmd = $cmd;
	}
}

//.doc, .dot
class MSOfficeDoc extends Office {

	public function getMimeType() {
		return '/application\/msword/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\MSOfficeDoc');

//.docm, .dotm, .xls(m), .xlt(m), .xla(m), .ppt(m), .pot(m), .pps(m), .ppa(m)
class MSOffice2003 extends Office {

	public function getMimeType() {
		return '/application\/vnd.ms-.*/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\MSOffice2003');

//.docx, .dotx, .xlsx, .xltx, .pptx, .potx, .ppsx
class MSOffice2007 extends Office {

	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.*/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\MSOffice2007');

//.odt, .ott, .oth, .odm, .odg, .otg, .odp, .otp, .ods, .ots, .odc, .odf, .odb, .odi, .oxt
class OpenDocument extends Office {
	
	public function getMimeType() {
		return '/application\/vnd.oasis.opendocument.*/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\OpenDocument');

//.sxw, .stw, .sxc, .stc, .sxd, .std, .sxi, .sti, .sxg, .sxm
class StarOffice extends Office {

	public function getMimeType() {
		return '/application\/vnd.sun.xml.*/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\StarOffice');