<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Listeners;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<LoadAdditionalScriptsEvent> */
class LoadAdditionalScriptsListener implements IEventListener {
	/** @var FederatedShareProvider */
	protected $federatedShareProvider;

	public function __construct(FederatedShareProvider $federatedShareProvider) {
		$this->federatedShareProvider = $federatedShareProvider;
	}

	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalScriptsEvent) {
			return;
		}

		if ($this->federatedShareProvider->isIncomingServer2serverShareEnabled()) {
			\OCP\Util::addScript('federatedfilesharing', 'external');
		}
	}
}
