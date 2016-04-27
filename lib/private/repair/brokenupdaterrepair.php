<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

use OC\Hooks\BasicEmitter;

/**
 * Class BrokenUpdaterRepair fixes some issues caused by bugs in the ownCloud
 * updater below version 9.0.2.
 *
 * FIXME: This file should be removed after the 9.0.2 release. The update server
 * is instructed to deliver 9.0.2 for 9.0.0 and 9.0.1.
 *
 * @package OC\Repair
 */
class BrokenUpdaterRepair extends BasicEmitter implements \OC\RepairStep {

	public function getName() {
		return 'Manually copies the third-party folder changes since 9.0.0 due ' .
		'to a bug in the updater.';
	}

	/**
	 * Manually copy the third-party files that have changed since 9.0.0 because
	 * the old updater does not copy over third-party changes.
	 *
	 * @return bool True if action performed, false otherwise
	 */
	private function manuallyCopyThirdPartyFiles() {
		$resourceDir = __DIR__ . '/../../../resources/updater-fixes/';
		$thirdPartyDir = __DIR__ . '/../../../3rdparty/';

		$filesToCopy = [
			// Composer updates
			'composer.json',
			'composer.lock',
			'composer/autoload_classmap.php',
			'composer/installed.json',
			'composer/LICENSE',
			// Icewind stream library
			'icewind/streams/src/DirectoryFilter.php',
			'icewind/streams/src/DirectoryWrapper.php',
			'icewind/streams/src/RetryWrapper.php',
			'icewind/streams/src/SeekableWrapper.php',
			// Sabre update
			'sabre/dav/CHANGELOG.md',
			'sabre/dav/composer.json',
			'sabre/dav/lib/CalDAV/Plugin.php',
			'sabre/dav/lib/CardDAV/Backend/PDO.php',
			'sabre/dav/lib/DAV/CorePlugin.php',
			'sabre/dav/lib/DAV/Version.php',
		];

		// Check the hash for the autoload_classmap.php file, if the hash does match
		// the expected value then the third-party folder has already been copied
		// properly.
		if(hash_file('sha512', $thirdPartyDir . '/composer/autoload_classmap.php') === 'abe09be19b6d427283cbfa7c4156d2c342cd9368d7d0564828a00ae02c435b642e7092cef444f94635f370dbe507eb6b2aa05109b32d8fb5d8a65c3a5a1c658f') {
			$this->emit('\OC\Repair', 'info', ['Third-party files seem already to have been copied. No repair necessary.']);
			return false;
		}

		foreach($filesToCopy as $file) {
			$state = copy($resourceDir . '/' . $file, $thirdPartyDir . '/' . $file);
			if($state === true) {
				$this->emit('\OC\Repair', 'info', ['Successfully replaced '.$file.' with new version.']);
			} else {
				$this->emit('\OC\Repair', 'warning', ['Could not replace '.$file.' with new version.']);
			}
		}
		return true;
	}

	/**
	 * Rerun the integrity check after the update since the repair step has
	 * repaired some invalid copied files.
	 */
	private function recheckIntegrity() {
		\OC::$server->getIntegrityCodeChecker()->runInstanceVerification();
	}

	public function run() {
		if($this->manuallyCopyThirdPartyFiles()) {
			$this->emit('\OC\Repair', 'info', ['Start integrity recheck.']);
			$this->recheckIntegrity();
			$this->emit('\OC\Repair', 'info', ['Finished integrity recheck.']);
		} else {
			$this->emit('\OC\Repair', 'info', ['Rechecking code integrity not necessary.']);
		}
	}
}

