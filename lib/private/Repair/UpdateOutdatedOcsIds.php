<?php
/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class UpdateOutdatedOcsIds is used to update invalid outdated OCS IDs, this is
 * for example the case when an application has had another OCS ID in the past such
 * as for contacts and calendar when apps.owncloud.com migrated to a unified identifier
 * for multiple versions.
 *
 * @package OC\Repair
 */
class UpdateOutdatedOcsIds implements IRepairStep {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'Repair outdated OCS IDs';
	}

	/**
	 * @param string $appName
	 * @param string $oldId
	 * @param string $newId
	 * @return bool True if updated, false otherwise
	 */
	public function fixOcsId($appName, $oldId, $newId) {
		$existingId = $this->config->getAppValue($appName, 'ocsid');

		if($existingId === $oldId) {
			$this->config->setAppValue($appName, 'ocsid', $newId);
			return true;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run(IOutput $output) {
		$appsToUpdate = [
			'contacts' => [
				'old' => '166044',
				'new' => '168708',
			],
			'calendar' => [
				'old' => '166043',
				'new' => '168707',
			],
			'bookmarks' => [
				'old' => '166042',
				'new' => '168710',
			],
			'search_lucene' => [
				'old' => '166057',
				'new' => '168709',
			],
			'documents' => [
				'old' => '166045',
				'new' => '168711',
			]
		];

		foreach($appsToUpdate as $appName => $ids) {
			if ($this->fixOcsId($appName, $ids['old'], $ids['new'])) {
				$output->info("Fixed invalid $appName OCS id");
			}
		}
	}
}
