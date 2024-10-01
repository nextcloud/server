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
	/** @var IShare */
	private $share;

	/** @var string */
	private $step;

	/** @var int */
	private $errorCode;

	/** @var string */
	private $errorMessage;

	public function __construct(IShare $share, string $step = '', int $errorCode = 200, string $errorMessage = '') {
		parent::__construct();
		$this->share = $share;
		$this->step = $step;
		$this->errorCode = $errorCode;
		$this->errorMessage = $errorMessage;
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
