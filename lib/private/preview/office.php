<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Preview;

abstract class Office extends Provider {
	private $cmd;

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$this->initCmd();
		if (is_null($this->cmd)) {
			return false;
		}

		$absPath = $fileview->toTmpFile($path);

		$tmpDir = \OC::$server->getTempManager()->getTempBaseDir();

		$defaultParameters = ' -env:UserInstallation=file://' . escapeshellarg($tmpDir . '/owncloud-' . \OC_Util::getInstanceId() . '/') . ' --headless --nologo --nofirststartwizard --invisible --norestore --convert-to pdf --outdir ';
		$clParameters = \OCP\Config::getSystemValue('preview_office_cl_parameters', $defaultParameters);

		$exec = $this->cmd . $clParameters . escapeshellarg($tmpDir) . ' ' . escapeshellarg($absPath);

		shell_exec($exec);

		//create imagick object from pdf
		$pdfPreview = null;
		try {
			list($dirname, , , $filename) = array_values(pathinfo($absPath));
			$pdfPreview = $dirname . '/' . $filename . '.pdf';

			$pdf = new \imagick($pdfPreview . '[0]');
			$pdf->setImageFormat('jpg');
		} catch (\Exception $e) {
			unlink($absPath);
			unlink($pdfPreview);
			\OCP\Util::writeLog('core', $e->getmessage(), \OCP\Util::ERROR);
			return false;
		}

		$image = new \OC_Image();
		$image->loadFromData($pdf);

		unlink($absPath);
		unlink($pdfPreview);

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);

			return $image;
		}
		return false;

	}

	private function initCmd() {
		$cmd = '';

		$libreOfficePath = \OC::$server->getConfig()->getSystemValue('preview_libreoffice_path', null);
		if (is_string($libreOfficePath)) {
			$cmd = $libreOfficePath;
		}

		$whichLibreOffice = shell_exec('command -v libreoffice');
		if ($cmd === '' && !empty($whichLibreOffice)) {
			$cmd = 'libreoffice';
		}

		$whichOpenOffice = shell_exec('command -v openoffice');
		if ($cmd === '' && !empty($whichOpenOffice)) {
			$cmd = 'openoffice';
		}

		if ($cmd === '') {
			$cmd = null;
		}

		$this->cmd = $cmd;
	}
}
