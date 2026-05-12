<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

class ImportLegacyStoragesService extends LegacyStoragesService {
	private $data;

	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of mount configs
	 */
	#[\Override]
	protected function readLegacyConfig() {
		return $this->data;
	}
}
