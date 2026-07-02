<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;

/**
 * Authorization gate event dispatched by a ShareReview source before deleting
 * an app-managed share on behalf of a ShareReview operator.
 *
 * Usage — dispatch and check:
 *
 *   $event = new ShareReviewAccessCheckEvent('MyApp', $shareId);
 *   $dispatcher->dispatchTyped($event);
 *   if (!$event->isHandled() || !$event->isGranted()) {
 *       return false; // default-deny: no listener means no access
 *   }
 *
 * Semantics:
 *  - Default-deny: an unhandled event blocks the deletion.
 *  - Deny wins: once denyAccess() is called, further grantAccess() calls are
 *    ignored and propagation is stopped immediately.
 *  - Multiple grants are harmless; the last listener to deny is authoritative.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
class ShareReviewAccessCheckEvent extends Event {

	private bool $handled = false;
	private bool $granted = false;
	private ?string $reason = null;

	/**
	 * @param string $sourceName Stable, non-translated identifier for the app
	 *                           registering the share source (e.g. 'Deck', 'Tables').
	 * @param string $shareId App-internal identifier of the share being deleted.
	 *
	 * @since 34.0.2
	 */
	public function __construct(
		private readonly string $sourceName,
		private readonly string $shareId,
	) {
		parent::__construct();
	}

	/**
	 * Stable, non-translated identifier of the app that owns this share source.
	 *
	 * @since 34.0.2
	 */
	public function getSourceName(): string {
		return $this->sourceName;
	}

	/**
	 * App-internal identifier of the share being deleted.
	 *
	 * @since 34.0.2
	 */
	public function getShareId(): string {
		return $this->shareId;
	}

	/**
	 * Grant access to delete the share.
	 *
	 * Has no effect if denyAccess() was already called on this event — deny wins.
	 *
	 * @since 34.0.2
	 */
	public function grantAccess(): void {
		if ($this->handled && !$this->granted) {
			return; // deny wins — a prior denyAccess() cannot be escalated to a grant
		}
		$this->handled = true;
		$this->granted = true;
	}

	/**
	 * Deny access and provide a human-readable reason.
	 *
	 * Stops event propagation immediately — no further listeners will run.
	 *
	 * @since 34.0.2
	 */
	public function denyAccess(string $reason): void {
		$this->handled = true;
		$this->granted = false;
		$this->reason = $reason;
		$this->stopPropagation();
	}

	/**
	 * Whether any listener has responded to this event.
	 *
	 * @since 34.0.2
	 */
	public function isHandled(): bool {
		return $this->handled;
	}

	/**
	 * Whether access was granted.
	 *
	 * @since 34.0.2
	 */
	public function isGranted(): bool {
		return $this->granted;
	}

	/**
	 * Human-readable denial reason, or null if access was granted or the event
	 * has not been handled yet.
	 *
	 * @since 34.0.2
	 */
	public function getReason(): ?string {
		return $this->reason;
	}
}
