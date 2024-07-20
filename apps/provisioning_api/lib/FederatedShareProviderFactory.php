<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\IServerContainer;

class FederatedShareProviderFactory {

	/** @var IServerContainer */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	public function get(): FederatedShareProvider {
		return $this->serverContainer->query(FederatedShareProvider::class);
	}
}
