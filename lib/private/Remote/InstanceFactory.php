<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote;

use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\Remote\IInstanceFactory;

class InstanceFactory implements IInstanceFactory {
	public function __construct(
		private ICache $cache,
		private IClientService $clientService,
	) {
	}

	public function getInstance($url) {
		return new Instance($url, $this->cache, $this->clientService);
	}
}
