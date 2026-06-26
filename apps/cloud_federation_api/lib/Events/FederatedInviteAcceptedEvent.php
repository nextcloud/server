<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\CloudFederationAPI\Events;

use OCA\CloudFederationAPI\Db\FederatedInvite;
use OCP\EventDispatcher\Event;

class FederatedInviteAcceptedEvent extends Event {
	public function __construct(
		private FederatedInvite $invitation,
	) {
		parent::__construct();
	}

	public function getInvitation(): FederatedInvite {
		return $this->invitation;
	}
}
