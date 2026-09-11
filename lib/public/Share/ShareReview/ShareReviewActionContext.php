<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent;

/**
 * Who a share-review action is performed for, and in which scope.
 *
 * A share-review app passes it to the mutators of
 * {@see IPaginatedShareReviewSource}, {@see IShareReviewSourceRemediation} and
 * {@see IShareReviewSourceSnapshot}; the owning app forwards both values
 * verbatim into the {@see ShareReviewAccessCheckEvent} it dispatches and never
 * decides them itself. That is how the listener authorizing the action learns
 * the acting user when the action does not run in that user's session (a
 * background job), and the scope when a user reviews their own shares rather
 * than the whole instance. Passing no context means the session user acts as
 * an operator, exactly as the 34.0.2 API behaves.
 *
 * @since 35.0.1
 */
#[Consumable(since: '35.0.1')]
final class ShareReviewActionContext {
	/**
	 * @param string|null $actingUserId The user the action is performed for;
	 *                                  null means the session user.
	 * @param ShareReviewAccessCheckEvent::SCOPE_* $scope Whether the acting
	 *                                                    user acts as an
	 *                                                    operator over all
	 *                                                    shares or on their
	 *                                                    own shares only.
	 *
	 * @throws \InvalidArgumentException on an unknown $scope
	 *
	 * @since 35.0.1
	 */
	public function __construct(
		public readonly ?string $actingUserId = null,
		public readonly string $scope = ShareReviewAccessCheckEvent::SCOPE_OPERATOR,
	) {
		if (!in_array($scope, ShareReviewAccessCheckEvent::SCOPES, true)) {
			throw new \InvalidArgumentException('Unknown share review scope');
		}
	}
}
