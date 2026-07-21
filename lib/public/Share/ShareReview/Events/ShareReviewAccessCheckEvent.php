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
 * Authorization gate for deleting an app-managed share through a share-review app.
 *
 * Background: Apps such as Deck or Tables manage their own shares outside of
 * the regular sharing backend ({@see \OCP\Share\IManager}). They can expose
 * those shares to a share-review app — a compliance tool that lets designated
 * operators audit and revoke shares across all apps — by implementing
 * {@see \OCP\Share\ShareReview\IShareReviewSource}. When a share-review
 * operator requests the deletion of such a share, the deletion is executed by
 * the app that owns the share, not by the share-review app. The owning app
 * has no way of knowing whether the acting user is actually authorized to
 * perform share reviews — only the share-review app knows that. This event
 * closes that gap: it lets the owning app ask "may the current user delete
 * this share on behalf of a share review?" before deleting anything.
 *
 * Dispatched by: the app that owns the share, i.e. the
 * {@see \OCP\Share\ShareReview\IShareReviewSource} implementation, at the
 * beginning of its deleteShare() method:
 *
 *   public function deleteShare(string $shareId): bool {
 *       $event = new ShareReviewAccessCheckEvent('MyApp', $shareId);
 *       $this->dispatcher->dispatchTyped($event);
 *       if (!$event->isHandled() || !$event->isGranted()) {
 *           return false; // default-deny: no listener means no access
 *       }
 *       // ... actually delete the share ...
 *   }
 *
 * Listened to by: the share-review app. Its listener decides whether the
 * current user is an authorized share-review operator (e.g. the app is
 * enabled for the user) and answers with grantAccess() or denyAccess():
 *
 *   public function handle(Event $event): void {
 *       if (!$event instanceof ShareReviewAccessCheckEvent) {
 *           return;
 *       }
 *       if ($this->isShareReviewOperator()) {
 *           $event->grantAccess();
 *       } else {
 *           $event->denyAccess('User is not a share-review operator.');
 *       }
 *   }
 *
 * Apps that merely expose shares must not listen to this event; answering it
 * is the responsibility of the share-review app that triggered the deletion.
 *
 * Semantics:
 *  - Default-deny: if no listener responds (isHandled() is false, e.g. no
 *    share-review app is installed), the dispatcher must not delete the share.
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
