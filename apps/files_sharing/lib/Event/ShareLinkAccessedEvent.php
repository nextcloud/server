<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
