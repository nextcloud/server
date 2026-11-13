<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use NCU\Sharing\Recipient\ShareRecipient;
use NCU\Sharing\Share;
use OCP\EventDispatcher\Event;

/**
 * Emitted after a recipient has been added to a share
 *
 * The share already contains the recipient.
 *
 * @experimental 36.0.0
 */
final class ShareRecipientAddedEvent extends Event {
	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		private readonly Share $share,
		private readonly ShareRecipient $recipient,
	) {
		parent::__construct();
	}

	/**
	 * @experimental 36.0.0
	 */
	public function getShare(): Share {
		return $this->share;
	}

	/**
	 * @experimental 36.0.0
	 */
	public function getRecipient(): ShareRecipient {
		return $this->recipient;
	}
}
