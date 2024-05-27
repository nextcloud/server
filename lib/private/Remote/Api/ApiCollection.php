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
	/** @var IInstance */
	private $instance;
	/** @var ICredentials */
	private $credentials;
	/** @var IClientService */
	private $clientService;

	public function __construct(IInstance $instance, ICredentials $credentials, IClientService $clientService) {
		$this->instance = $instance;
		$this->credentials = $credentials;
		$this->clientService = $clientService;
	}

	public function getCapabilitiesApi() {
		return new OCS($this->instance, $this->credentials, $this->clientService);
	}

	public function getUserApi() {
		return new OCS($this->instance, $this->credentials, $this->clientService);
	}
}
