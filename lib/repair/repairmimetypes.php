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

		$stmt = \OC_DB::prepare('
			UPDATE `*PREFIX*mimetypes`
			SET    `mimetype` = ?
			WHERE  `mimetype` = ?
		');

		foreach ($wrongMimetypes as $wrong => $correct) {
			\OC_DB::executeAudited($stmt, array($wrong, $correct));
		}

		$updatedMimetypes = array(
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xlsx' => 'application/vnd.ms-excel',
			'pptx' => 'application/vnd.ms-powerpoint',
		);

		// separate doc from docx etc
		foreach ($updatedMimetypes as $extension => $mimetype ) {
			$result = \OC_DB::executeAudited('
				SELECT count(`mimetype`)
				FROM   `*PREFIX*mimetypes`
				WHERE  `mimetype` = ?
				', array($mimetype)
			);

			$exists = $result->fetchOne();

			if ( ! $exists ) {
				// insert mimetype
				\OC_DB::executeAudited('
					INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
					VALUES ( ? )
					', array($mimetype)
				);
			}

			// change mimetype for files with x extension
			\OC_DB::executeAudited('
				UPDATE `*PREFIX*filecache`
				SET `mimetype` = (
					SELECT `id`
					FROM `*PREFIX*mimetypes`
					WHERE `mimetype` = ?
				) WHERE `name` LIKE ?
				', array($mimetype, '%.'.$extension)
			);
		}
		return true;
	}

	/**
	 * Fix mime types
	 */
	public function run() {
		// TODO: check precondition to avoid running the fix every time
		if ($this->fixOfficeMimeTypes()) {
			$this->emit('\OC\Repair', 'info', array('Fixed office mime types'));
		}
	}
}

