<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use NCU\Sharing\Share;
use NCU\Sharing\ShareState;
use OCP\EventDispatcher\Event;

/**
 * Emitted after the state of a share has been updated
 *
 * The share contains the resulting state, which can differ from the requested one if the share is not valid for that state.
 *
 * @experimental 36.0.0
 */
final class ShareStateUpdatedEvent extends Event {
	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		private readonly Share $share,
		private readonly ShareState $previousState,
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
	public function getPreviousState(): ShareState {
		return $this->previousState;
	}
}
