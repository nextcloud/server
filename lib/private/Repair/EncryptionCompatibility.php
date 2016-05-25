<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Repair;

use OCP\App;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EncryptionCompatibility implements IRepairStep {
	private $affectedMd5 = ['19efc6cf053d0248e45407e7cc39b39b', '6752909b79ffe71237a5db5d1f8f1b65'];
	private $targetPath = 'encryption/lib/crypto/encryption.php';

	public function getName() {
		return 'Repair encryption app incompatibility';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$filePath = \OC::$SERVERROOT . '/__apps/' . $this->targetPath;
		if ($this->isAffected($filePath)){
			$resourceDir = __DIR__ . '/../../../resources/updater-fixes/apps/';
			$isCopied = copy($resourceDir . $this->targetPath, $filePath);
			if ($isCopied){
				$output->info('Successfully replaced ' . $filePath . ' with new version.');
			} else {
				$output->warning('Could not replace ' . $filePath . ' with new version.');
			}
		} else {
			$output->info('No repair necessary');
		}
	}

	/**
	 * @param string $filePath
	 * Checks whether encryption is enabled and target revisions exist
	 * @return bool
	 */
	protected function isAffected($filePath){
		return App::isEnabled('encryption')
			&& file_exists($filePath)
			&& in_array(md5_file($filePath), $this->affectedMd5)
		;
	}
}
