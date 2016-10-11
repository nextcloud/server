<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Stefan Weil <sw@weilnetz.de>
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
$installedVersion = \OC::$server->getConfig()->getAppValue('files', 'installed_version');
$ocVersion = explode('.', \OC::$server->getSystemConfig()->getValue('version'));

/**
 * In case encryption was not enabled, we accidentally set encrypted = 1 for
 * files inside mount points, since 8.1.0. This breaks opening the files in
 * 8.1.1 because we fixed the code that checks if a file is encrypted.
 * In order to fix the file, we need to reset the flag of the file. However,
 * the flag might be set because the file is in fact encrypted because it was
 * uploaded at a time where encryption was enabled.
 *
 * So we can only do this when:
 * - Current version of ownCloud before the update is 8.1.0 or 8.2.0.(0-2)
 * - Encryption is disabled
 * - files_encryption is not known in the app config
 *
 * If the first two are not the case, we are save. However, if files_encryption
 * values exist in the config, we might have a false negative here.
 * Now if there is no file with unencrypted size greater 0, that means there are
 * no files that are still encrypted with "files_encryption" encryption. So we
 * can also safely reset the flag here.
 *
 * If this is not the case, we go with "better save then sorry" and don't change
 * the flag but write a message to the ownCloud log file.
 */

/**
 * @param \OCP\IDBConnection $conn
 */
function owncloud_reset_encrypted_flag(\OCP\IDBConnection $conn) {
	$conn->executeUpdate('UPDATE `*PREFIX*filecache` SET `encrypted` = 0 WHERE `encrypted` = 1');
}

// Current version of ownCloud before the update is 8.1.0 or 8.2.0.(0-2)
if ($installedVersion === '1.1.9' && (
		// 8.1.0.x
		(((int) $ocVersion[0]) === 8 && ((int) $ocVersion[1]) === 1 && ((int) $ocVersion[2]) === 0)
		||
		// < 8.2.0.3
		(((int) $ocVersion[0]) === 8 && ((int) $ocVersion[1]) === 2 && ((int) $ocVersion[2]) === 0 && ((int) $ocVersion[3]) < 3)
	)) {

	// Encryption is not enabled
	if (!\OC::$server->getEncryptionManager()->isEnabled()) {
		$conn = \OC::$server->getDatabaseConnection();

		// Old encryption is not known in app config
		$oldEncryption = \OC::$server->getConfig()->getAppKeys('files_encryption');
		if (empty($oldEncryption)) {
			owncloud_reset_encrypted_flag($conn);
		} else {
			$query = $conn->prepare('SELECT * FROM `*PREFIX*filecache` WHERE `encrypted` = 1 AND `unencrypted_size` > 0', 1);
			$query->execute();
			$empty = $query->fetch();

			if (empty($empty)) {
				owncloud_reset_encrypted_flag($conn);
			} else {
				/**
				 * Sorry in case you are a false positive, but we are not 100% that
				 * you don't have any encrypted files anymore, so we can not reset
				 * the value safely
				 */
				\OC::$server->getLogger()->warning(
					'If you have a problem with files not being accessible and '
					. 'you are not using encryption, please have a look at the following'
					. 'issue: {issue}',
					[
						'issue' => 'https://github.com/owncloud/core/issues/17846',
					]
				);
			}
		}
	}
}
