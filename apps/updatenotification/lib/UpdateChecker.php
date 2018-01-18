<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\UpdateNotification;

use OC\Updater\VersionCheck;

class UpdateChecker {
	/** @var VersionCheck */
	private $updater;

	/**
	 * @param VersionCheck $updater
	 */
	public function __construct(VersionCheck $updater) {
		$this->updater = $updater;
	}

	/**
	 * @return array
	 */
	public function getUpdateState(): array {
		$data = $this->updater->check();
		$result = [];

		if (isset($data['version']) && $data['version'] !== '' && $data['version'] !== []) {
			$result['updateAvailable'] = true;
			$result['updateVersion'] = $data['versionstring'];
			$result['updaterEnabled'] = $data['autoupdater'] === '1';
			if (strpos($data['web'], 'https://') === 0) {
				$result['updateLink'] = $data['web'];
			}
			if (strpos($data['url'], 'https://') === 0) {
				$result['downloadLink'] = $data['url'];
			}

			return $result;
		}

		return [];
	}

	/**
	 * @param array $data
	 */
	public function populateJavaScriptVariables(array $data) {
		$data['array']['oc_updateState'] =  json_encode([
			'updateAvailable' => true,
			'updateVersion' => $this->getUpdateState()['updateVersion'],
			'updateLink' => $this->getUpdateState()['updateLink'] ?? '',
		]);
	}
}
