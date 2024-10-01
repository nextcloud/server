<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote\Api;

use OCP\Http\Client\IClientService;
use OCP\Remote\Api\IApiFactory;
use OCP\Remote\ICredentials;
use OCP\Remote\IInstance;

class ApiFactory implements IApiFactory {
	/** @var IClientService */
	private $clientService;

	public function __construct(IClientService $clientService) {
		$this->clientService = $clientService;
	}

	public function getApiCollection(IInstance $instance, ICredentials $credentials) {
		return new ApiCollection($instance, $credentials, $this->clientService);
	}
}
