<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

class AppdataPreviewObjectStoreStorage extends ObjectStoreStorage {
	private string $internalId;

	/**
	 * @param array $params
	 * @throws \Exception
	 */
	public function __construct($params) {
		if (!isset($params['internal-id'])) {
			throw new \Exception('missing id in parameters');
		}
		$this->internalId = (string)$params['internal-id'];
		parent::__construct($params);
	}

	public function getId(): string {
		return 'object::appdata::preview:' . $this->internalId;
	}
}
