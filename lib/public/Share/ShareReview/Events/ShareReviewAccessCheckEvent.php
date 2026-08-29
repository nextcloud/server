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
 * Authorization gate for acting on an app-managed share through a share-review
 * app: deleting it, remediating it (password, expiration) or restoring it from
 * a snapshot.
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
 * Actions and scopes (since 36.0.0): the same gate covers every operation a
 * share-review app can request, so the owning app dispatches one event type
 * and the share-review app answers it from one listener:
 *  - ACTION_DELETE — dispatched by {@see \OCP\Share\ShareReview\IShareReviewSource::deleteShare()}.
 *    This is the default and the only action of the 34.0.2 event.
 *  - ACTION_REMEDIATE — dispatched by the mutators of
 *    {@see \OCP\Share\ShareReview\IShareReviewSourceRemediation} before a
 *    password or expiration date is changed.
 *  - ACTION_RESTORE — dispatched by
 *    {@see \OCP\Share\ShareReview\IShareReviewSourceSnapshot::restoreShare()}
 *    before a share is re-created from a snapshot.
 *  - SCOPE_OPERATOR (default) — the acting user is a share-review operator
 *    reviewing the whole instance; the listener grants based on operator
 *    membership, exactly as for the 34.0.2 event.
 *  - SCOPE_SELF — the acting user reviews their own shares (a personal
 *    self-audit). The listener must additionally verify, e.g. through
 *    {@see \OCP\Share\ShareReview\IPaginatedShareReviewSource::getShare()},
 *    that the acting user is the initiator of the share before granting.
 *    For ACTION_RESTORE the share no longer exists, so initiatorship must
 *    be verified against the initiator recorded with the snapshot instead.
 *  - The acting user defaults to the session user (null); a background job
 *    acting for a user passes the user id explicitly. The listener must use
 *    getActingUserId() when set instead of the session.
 * Listeners that predate 36.0.0 see every action as a plain access check and
 * grant or deny by operator membership: non-operators stay denied (fail
 * closed), but for operators the new actions extend the granted capability
 * set — ACTION_REMEDIATE includes removing a link share's password, which
 * can expose content deletion never could. A listener that distinguishes
 * reviewers with delete-only rights must check getAction() and deny actions
 * it does not recognize.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
class ShareReviewAccessCheckEvent extends Event {
	/**
	 * The share is about to be deleted
	 * @since 36.0.0
	 */
	public const ACTION_DELETE = 'delete';
	/**
	 * The share's password or expiration date is about to be changed
	 * @since 36.0.0
	 */
	public const ACTION_REMEDIATE = 'remediate';
	/**
	 * The share is about to be re-created from a snapshot
	 * @since 36.0.0
	 */
	public const ACTION_RESTORE = 'restore';

	/**
	 * The acting user reviews the whole instance as a share-review operator
	 * @since 36.0.0
	 */
	public const SCOPE_OPERATOR = 'operator';
	/**
	 * The acting user reviews their own shares only
	 * @since 36.0.0
	 */
	public const SCOPE_SELF = 'self';

	private bool $handled = false;
	private bool $granted = false;
	private ?string $reason = null;

	/**
	 * @param string $sourceName Stable, non-translated identifier for the app
	 *                           registering the share source (e.g. 'Deck', 'Tables').
	 * @param string $shareId App-internal identifier of the share being acted on.
	 * @param self::ACTION_* $action The operation being authorized (since 36.0.0).
	 * @param string|null $actingUserId The user the operation is performed
	 *                                  for; null means the session user
	 *                                  (since 36.0.0).
	 * @param self::SCOPE_* $scope Whether the acting user acts as an operator
	 *                             over all shares or on their own shares only
	 *                             (since 36.0.0).
	 *
	 * @throws \InvalidArgumentException on an unknown $action or $scope
	 *
	 * @since 34.0.2
	 */
	public function __construct(
		private readonly string $sourceName,
		private readonly string $shareId,
		private readonly string $action = self::ACTION_DELETE,
		private readonly ?string $actingUserId = null,
		private readonly string $scope = self::SCOPE_OPERATOR,
	) {
		parent::__construct();
		if (!in_array($action, [self::ACTION_DELETE, self::ACTION_REMEDIATE, self::ACTION_RESTORE], true)) {
			throw new \InvalidArgumentException('Unknown share review action');
		}
		if (!in_array($scope, [self::SCOPE_OPERATOR, self::SCOPE_SELF], true)) {
			throw new \InvalidArgumentException('Unknown share review scope');
		}
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
	 * App-internal identifier of the share being acted on.
	 *
	 * @since 34.0.2
	 */
	public function getShareId(): string {
		return $this->shareId;
	}

	/**
	 * The operation being authorized, one of the ACTION_* constants.
	 *
	 * @return self::ACTION_*
	 * @since 36.0.0
	 */
	public function getAction(): string {
		return $this->action;
	}

	/**
	 * The user the operation is performed for, or null for the session user.
	 *
	 * @since 36.0.0
	 */
	public function getActingUserId(): ?string {
		return $this->actingUserId;
	}

	/**
	 * Whether the acting user acts as an operator over all shares or on their
	 * own shares only, one of the SCOPE_* constants.
	 *
	 * @return self::SCOPE_*
	 * @since 36.0.0
	 */
	public function getScope(): string {
		return $this->scope;
	}

	/**
	 * Grant access to perform the action on the share.
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
