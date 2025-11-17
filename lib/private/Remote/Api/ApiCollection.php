<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote\Api;

use OCP\Http\Client\IClientService;
use OCP\Remote\Api\IApiCollection;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

class ApiCollection implements IApiCollection {
	public function __construct(
		private IInstance $instance,
		private ICredentials $credentials,
		private IClientService $clientService,
	) {
	}

	public function getCapabilitiesApi() {
		return new OCS($this->instance, $this->credentials, $this->clientService);
	}

	public function getUserApi() {
		return new OCS($this->instance, $this->credentials, $this->clientService);
	}
}
