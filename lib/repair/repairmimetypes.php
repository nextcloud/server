<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * Copyright (c) 2014 Jörn Dreyer jfd@owncloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;

class RepairMimeTypes extends BasicEmitter implements \OC\RepairStep {

	public function getName() {
		return 'Repair mime types';
	}

	private function fixOfficeMimeTypes() {
		// update wrong mimetypes
		$wrongMimetypes = array(
			'application/mspowerpoint' => 'application/vnd.ms-powerpoint',
			'application/msexcel' => 'application/vnd.ms-excel',
		);

		$existsStmt = \OC_DB::prepare('
			SELECT count(`mimetype`)
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');

		$getIdStmt = \OC_DB::prepare('
			SELECT `id`
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');

		$insertStmt = \OC_DB::prepare('
			INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
			VALUES ( ? )
		');

		$updateWrongStmt = \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `mimetype` = ?
		');

		$deleteStmt = \OC_DB::prepare('
			DELETE FROM `*PREFIX*mimetypes`
			WHERE `id` = ?
		');

		foreach ($wrongMimetypes as $wrong => $correct) {


			// do we need to remove a wrong mimetype?
			$result = \OC_DB::executeAudited($getIdStmt, array($wrong));
			$wrongId = $result->fetchOne();

			if ($wrongId !== false) {

				// do we need to insert the correct mimetype?
				$result = \OC_DB::executeAudited($existsStmt, array($correct));
				$exists = $result->fetchOne();

				if ( ! $exists ) {
					// insert mimetype
					\OC_DB::executeAudited($insertStmt, array($correct));
				}

				// change wrong mimetype to correct mimetype in filecache
				\OC_DB::executeAudited($updateWrongStmt, array($correct, $wrongId));

				// delete wrong mimetype
				\OC_DB::executeAudited($deleteStmt, array($wrongId));

			}

		}

		$updatedMimetypes = array(
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		);

		$updateByNameStmt = \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `name` LIKE ?
		');

		// separate doc from docx etc
		foreach ($updatedMimetypes as $extension => $mimetype ) {
			$result = \OC_DB::executeAudited($existsStmt, array($mimetype));
			$exists = $result->fetchOne();

			if ( ! $exists ) {
				// insert mimetype
				\OC_DB::executeAudited($insertStmt, array($mimetype));
			}

			// change mimetype for files with x extension
			\OC_DB::executeAudited($updateByNameStmt, array($mimetype, '%.'.$extension));
		}
	}

	private function fixAPKMimeType() {
		$existsStmt = \OC_DB::prepare('
			SELECT count(`mimetype`)
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');

		$insertStmt = \OC_DB::prepare('
			INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
			VALUES ( ? )
		');


		$updateByNameStmt = \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `name` LIKE ?
		');


		$mimeTypeExtension = 'apk';
		$mimeTypeName = 'application/vnd.android.package-archive';

		$result = \OC_DB::executeAudited($existsStmt, array($mimeTypeName));
		$exists = $result->fetchOne();

		if ( ! $exists ) {
			// insert mimetype
			\OC_DB::executeAudited($insertStmt, array($mimeTypeName));
		}

		// change mimetype for files with x extension
		\OC_DB::executeAudited($updateByNameStmt, array($mimeTypeName, '%.'.$mimeTypeExtension));
	}

	/**
	 * Fix mime types
	 */
	public function run() {
		if ($this->fixOfficeMimeTypes()) {
			$this->emit('\OC\Repair', 'info', array('Fixed office mime types'));
		}

		if ($this->fixAPKMimeType()) {
			$this->emit('\OC\Repair', 'info', array('Fixed APK mime type'));
		}
	}
}

