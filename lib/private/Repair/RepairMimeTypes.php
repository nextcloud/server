<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Faruk Uzun <farukuzun@collabora.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Normal Ra <normalraw@gmail.com>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\Repair;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairMimeTypes implements IRepairStep {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var int
	 */
	protected $folderMimeTypeId;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	public function getName() {
		return 'Repair mime types';
	}

	private static function existsStmt() {
		return \OC_DB::prepare('
			SELECT count(`mimetype`)
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');
	}

	private static function getIdStmt() {
		return \OC_DB::prepare('
			SELECT `id`
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
		');
	}

	private static function insertStmt() {
		return \OC_DB::prepare('
			INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
			VALUES ( ? )
		');
	}

	private static function updateByNameStmt() {
		return \OC_DB::prepare('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = ?
			WHERE `mimetype` <> ? AND `mimetype` <> ? AND `name` ILIKE ?
		');
	}

	private function updateMimetypes($updatedMimetypes) {
		if (empty($this->folderMimeTypeId)) {
			$result = \OC_DB::executeAudited(self::getIdStmt(), array('httpd/unix-directory'));
			$this->folderMimeTypeId = (int)$result->fetchOne();
		}

		foreach ($updatedMimetypes as $extension => $mimetype) {
			$result = \OC_DB::executeAudited(self::existsStmt(), array($mimetype));
			$exists = $result->fetchOne();

			if (!$exists) {
				// insert mimetype
				\OC_DB::executeAudited(self::insertStmt(), array($mimetype));
			}
			
			// get target mimetype id
			$result = \OC_DB::executeAudited(self::getIdStmt(), array($mimetype));
			$mimetypeId = $result->fetchOne();

			// change mimetype for files with x extension
			\OC_DB::executeAudited(self::updateByNameStmt(), array($mimetypeId, $this->folderMimeTypeId, $mimetypeId, '%.' . $extension));
		}
	}

	private function introduceImageTypes() {
		$updatedMimetypes = array(
			'jp2' => 'image/jp2',
			'webp' => 'image/webp',
		);

		$this->updateMimetypes($updatedMimetypes);
	}

	private function introduceWindowsProgramTypes() {
		$updatedMimetypes = array(
			'htaccess' => 'text/plain',
			'bat' => 'application/x-msdos-program',
			'cmd' => 'application/cmd',
		);

		$this->updateMimetypes($updatedMimetypes);
	}

	/**
	 * Fix mime types
	 */
	public function run(IOutput $out) {

		$ocVersionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');

		// NOTE TO DEVELOPERS: when adding new mime types, please make sure to
		// add a version comparison to avoid doing it every time

		if (version_compare($ocVersionFromBeforeUpdate, '12.0.0.14', '<') && $this->introduceImageTypes()) {
			$out->info('Fixed image mime types');
		}

		if (version_compare($ocVersionFromBeforeUpdate, '12.0.0.13', '<') && $this->introduceWindowsProgramTypes()) {
			$out->info('Fixed windows program mime types');
		}
	}
}
