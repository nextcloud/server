<?php
/**
 * @copyright Copyright (c) 2024 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair\NC29;

use OC\Files\View;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;


class MoveCertificateBundles implements IRepairStep {

	const OLD_PATH = '/files_external';
	const ROOT_CERTS_FILENAME = '/rootcerts.crt';
	const CERTS_UPLOAD_PATH = '/uploads';

	protected string $newRootPath;

	public function __construct(protected View $view, protected IConfig $config) {
		$this->newRootPath = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . '/data/certificate_manager';
	}

	public function getName(): string {
		return 'Move the certificate bundles from data/files_external/ to data/certificate_manager/';
	}


	public function run(IOutput $output): void {
		if (!$this->shouldRun()) {
			return;
		}

		$oldCertificateBundlePath = self::OLD_PATH . self::ROOT_CERTS_FILENAME;
		$oldUploadsPath = self::OLD_PATH . self::CERTS_UPLOAD_PATH;

		$this->view->copy($oldUploadsPath, $this->newRootPath . self::CERTS_UPLOAD_PATH, true);
		$this->view->copy($oldCertificateBundlePath, $this->newRootPath . self::ROOT_CERTS_FILENAME, true);

		$this->view->unlink($oldCertificateBundlePath);
		$this->view->unlink($oldUploadsPath);
	}

	protected function shouldRun(): bool {
		return $this->view->file_exists($this->newRootPath . self::ROOT_CERTS_FILENAME);
	}
}
