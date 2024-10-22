<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Event;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

class ShareLinkAccessedEvent extends Event {
	public function __construct(
		private IShare $share,
		private string $step = '',
		private int $errorCode = 200,
		private string $errorMessage = '',
	) {
		parent::__construct();
	}

	public function getShare(): IShare {
		return $this->share;
	}

	public function getStep(): string {
		return $this->step;
	}

	public function getErrorCode(): int {
		return $this->errorCode;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}
}
