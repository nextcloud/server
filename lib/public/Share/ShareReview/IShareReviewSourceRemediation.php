<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Optional capability of a share-review source: remediate a share instead of
 * revoking it, by setting or removing a password or an expiration date.
 *
 * A share-review app discovers the capability through `instanceof` and hides
 * the corresponding controls for sources that do not implement it. An
 * implementation must gate both mutators exactly like
 * {@see IShareReviewSource::deleteShare()}: dispatch a
 * {@see \OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent} with action
 * {@see \OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent::ACTION_REMEDIATE}
 * first, carrying the acting user and scope of the given
 * {@see ShareReviewActionContext}, and refuse unless access was granted.
 *
 * A source may support only one of the two remediations; it announces which
 * through canSetPassword() and canSetExpiration(), and the mutator of an
 * unsupported remediation must return false.
 *
 * Both capabilities are declared per source, not per share. Passwords and
 * expiration dates exist on link and mail shares only, so a share-review app
 * offers remediation for entries of type {@see \OCP\Share\IShare::TYPE_LINK}
 * and {@see \OCP\Share\IShare::TYPE_EMAIL}, and a mutator called for any
 * other entry returns false without dispatching the access check.
 *
 * @since 35.0.1
 */
#[Implementable(since: '35.0.1')]
interface IShareReviewSourceRemediation extends IShareReviewSource {
	/**
	 * Whether this source can set or remove share passwords.
	 *
	 * @since 35.0.1
	 */
	public function canSetPassword(): bool;

	/**
	 * Whether this source can set or remove share expiration dates.
	 *
	 * @since 35.0.1
	 */
	public function canSetExpiration(): bool;

	/**
	 * Set the password of a share, or remove it when null is passed.
	 *
	 * @param string $shareId The deletion identifier of the share, as accepted
	 *                        by {@see IShareReviewSource::deleteShare()}.
	 * @param string|null $password The new plain-text password, or null to
	 *                              remove password protection.
	 * @param ShareReviewActionContext|null $context Who acts, and in which
	 *                                               scope; forwarded into the
	 *                                               access-check event.
	 * @return bool whether the share was updated
	 *
	 * @since 35.0.1
	 */
	public function setPassword(string $shareId, ?string $password, ?ShareReviewActionContext $context = null): bool;

	/**
	 * Set the expiration date of a share, or remove it when null is passed.
	 *
	 * @param string $shareId The deletion identifier of the share, as accepted
	 *                        by {@see IShareReviewSource::deleteShare()}.
	 * @param int|null $expirationTimestamp The new expiration as a Unix
	 *                                      timestamp, or null to remove it.
	 * @param ShareReviewActionContext|null $context Who acts, and in which
	 *                                               scope; forwarded into the
	 *                                               access-check event.
	 * @return bool whether the share was updated
	 *
	 * @since 35.0.1
	 */
	public function setExpiration(string $shareId, ?int $expirationTimestamp, ?ShareReviewActionContext $context = null): bool;
}
