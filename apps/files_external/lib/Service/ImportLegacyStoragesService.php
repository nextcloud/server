<?php

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Service;

class ImportLegacyStoragesService extends LegacyStoragesService {
	private $data;

	/**
	 * @param BackendService $backendService
	 */
	public function __construct(BackendService $backendService) {
		$this->backendService = $backendService;
	}

	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * Read legacy config data
	 *
	 * @return array list of mount configs
	 */
	protected function readLegacyConfig() {
		return $this->data;
	}
}
