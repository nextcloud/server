<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Event;

use OCP\EventDispatcher\Event;
use OCP\Mail\IMessage;
use OCP\Share\IShare;

/**
 * Base class for all BeforeShare*MailSentEvent types.
 *
 * Carries the fully-prepared IMessage (already rendered) and the resolved
 * recipient list. Listeners call markMailHandled() to suppress the native
 * mailer->send().
 */
abstract class AbstractBeforeShareMailSentEvent extends Event {
	private bool $mailHandled = false;

	/**
	 * @param string[] $resolvedEmails resolved recipient addresses (not guaranteed to be validated)
	 */
	public function __construct(
		private readonly IShare $share,
		private readonly array $resolvedEmails,
		private readonly IMessage $message,
	) {
		parent::__construct();
	}

	public function getShare(): IShare {
		return $this->share;
	}

	/** @return string[] */
	public function getResolvedEmails(): array {
		return $this->resolvedEmails;
	}

	public function getMessage(): IMessage {
		return $this->message;
	}

	/**
	 * Call to suppress the native mailer->send() for this message.
	 * Must be called before any send attempt — if the listener's own send
	 * throws, the exception propagates and the native send is also skipped.
	 */
	public function markMailHandled(): void {
		$this->mailHandled = true;
	}

	public function isMailHandled(): bool {
		return $this->mailHandled;
	}
}
