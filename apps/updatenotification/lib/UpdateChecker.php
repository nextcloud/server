<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification;

use OC\Updater\ChangesCheck;
use OC\Updater\VersionCheck;

class UpdateChecker {
	/** @var VersionCheck */
	private $updater;
	/** @var ChangesCheck */
	private $changesCheck;

	/**
	 * @param VersionCheck $updater
	 */
	public function __construct(VersionCheck $updater, ChangesCheck $changesCheck) {
		$this->updater = $updater;
		$this->changesCheck = $changesCheck;
	}

	/**
	 * @return array
	 */
	public function getUpdateState(): array {
		$data = $this->updater->check();
		$result = [];

		if (isset($data['version']) && $data['version'] !== '' && $data['version'] !== []) {
			$result['updateAvailable'] = true;
			$result['updateVersion'] = $data['version'];
			$result['updateVersionString'] = $data['versionstring'];
			$result['updaterEnabled'] = $data['autoupdater'] === '1';
			$result['versionIsEol'] = $data['eol'] === '1';
			if (strpos($data['web'], 'https://') === 0) {
				$result['updateLink'] = $data['web'];
			}
			if (strpos($data['url'], 'https://') === 0) {
				$result['downloadLink'] = $data['url'];
			}
			if (strpos($data['changes'], 'https://') === 0) {
				try {
					$result['changes'] = $this->changesCheck->check($data['changes'], $data['version']);
				} catch (\Exception $e) {
					// no info, not a problem
				}
			}

			return $result;
		}

		return [];
	}

	/**
	 * @param array $data
	 */
	public function populateJavaScriptVariables(array $data) {
		$data['array']['oc_updateState'] = json_encode([
			'updateAvailable' => true,
			'updateVersion' => $this->getUpdateState()['updateVersionString'],
			'updateLink' => $this->getUpdateState()['updateLink'] ?? '',
		]);
	}
}
