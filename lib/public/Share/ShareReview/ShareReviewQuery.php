<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Share\IShare;

/**
 * Pagination, sorting, search and filter parameters for one page of a
 * {@see IPaginatedShareReviewSource}.
 *
 * The contract is fixed and identical for every source, so a share-review app
 * can offer the same table controls on every tab:
 *
 *  - Sortable: time, object, initiator, recipient, type (see the SORT_*
 *    constants). Sources MUST append their primary key as a secondary sort in
 *    the same direction so equal-keyed rows never straddle page boundaries
 *    nondeterministically, and MUST order NULL sort keys last in both
 *    directions (databases disagree on the default placement).
 *  - Searchable ($search): case-insensitive substring across object,
 *    initiator and recipient (a link share's token counts as its recipient),
 *    OR-combined across those three fields and never across more or fewer.
 *  - Filterable: every remaining property below. All filters combine with
 *    AND — with each other and with $search — except that within the same
 *    identity field the scoped substring and the exact id list combine with
 *    OR (initiatorSearch OR initiatorIds, recipientSearch OR recipientIds),
 *    which lets a consumer resolve display names to ids and search both.
 *  - Permissions are neither sortable nor searchable; they are filterable by
 *    their opaque {@see ShareReviewPermission::$id} only.
 *
 * Every substring input (search, initiatorSearch, recipientSearch,
 * objectSearch) MUST be escaped for LIKE wildcards by the source before being
 * wrapped in `%…%`; sort fields MUST resolve through a fixed whitelist to
 * column names. User input must never be interpolated into SQL.
 *
 * The sort and pagination fields (limit, offset, sortField, sortDescending)
 * are ignored by {@see IPaginatedShareReviewSource::countShares()} and
 * {@see IPaginatedShareReviewSource::countSharesByType()}.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class ShareReviewQuery {
	/**
	 * Sort by {@see ShareReviewEntry::$lastModifiedTimestamp}
	 * @since 36.0.0
	 */
	public const SORT_TIME = 'time';
	/**
	 * Sort by {@see ShareReviewEntry::$object}
	 * @since 36.0.0
	 */
	public const SORT_OBJECT = 'object';
	/**
	 * Sort by {@see ShareReviewEntry::$initiator}
	 * @since 36.0.0
	 */
	public const SORT_INITIATOR = 'initiator';
	/**
	 * Sort by {@see ShareReviewEntry::$recipient}
	 * @since 36.0.0
	 */
	public const SORT_RECIPIENT = 'recipient';
	/**
	 * Sort by {@see ShareReviewEntry::$type}
	 * @since 36.0.0
	 */
	public const SORT_TYPE = 'type';

	/**
	 * All valid values for $sortField
	 * @since 36.0.0
	 */
	public const SORTABLE_FIELDS = [
		self::SORT_TIME,
		self::SORT_OBJECT,
		self::SORT_INITIATOR,
		self::SORT_RECIPIENT,
		self::SORT_TYPE,
	];

	/**
	 * Upper bound for $limit
	 * @since 36.0.0
	 */
	public const MAX_LIMIT = 500;

	/**
	 * @param int $limit Page size, 1..MAX_LIMIT.
	 * @param int $offset Number of rows to skip, >= 0.
	 * @param string|null $search Case-insensitive substring matched against
	 *                            object, initiator and recipient (OR).
	 * @param self::SORT_* $sortField Field to sort by.
	 * @param bool $sortDescending Sort direction; the default lists the most
	 *                             recently modified shares first.
	 * @param int|null $modifiedSinceTimestamp Unix timestamp; only shares
	 *                                         whose lastModifiedTimestamp is
	 *                                         strictly greater match (the
	 *                                         "new since last review" filter).
	 * @param list<IShare::TYPE_*>|null $shareTypes Exact-match share types
	 *                                              (IN); null matches all types.
	 *                                              A requested type the source
	 *                                              cannot produce — and, as for
	 *                                              every list filter, an empty
	 *                                              list — matches nothing.
	 * @param bool|null $hasPassword null = no filter, true = only
	 *                               password-protected shares, false = only
	 *                               unprotected ones. Defined against
	 *                               {@see ShareReviewEntry::$hasPassword}.
	 * @param bool|null $hasExpiration null = no filter, true = only shares with
	 *                                 an expiration date, false = only shares
	 *                                 without one. Defined against
	 *                                 {@see ShareReviewEntry::$expirationTimestamp}
	 *                                 being non-null.
	 * @param int|null $expiresAfterTimestamp Unix timestamp; inclusive lower
	 *                                        bound on the expiration date.
	 *                                        Matches only shares that have one.
	 * @param int|null $expiresBeforeTimestamp Unix timestamp; exclusive upper
	 *                                         bound on the expiration date.
	 *                                         Matches only shares that have one.
	 *                                         Together with the lower bound this
	 *                                         forms the half-open range
	 *                                         `after <= expiration < before`.
	 * @param string|null $initiatorSearch Case-insensitive substring scoped to
	 *                                     the initiator; OR-combined with
	 *                                     $initiatorIds.
	 * @param string|null $recipientSearch Case-insensitive substring scoped to
	 *                                     the recipient (including a link
	 *                                     share's token); OR-combined with
	 *                                     $recipientIds.
	 * @param string|null $objectSearch Case-insensitive substring scoped to the
	 *                                  object.
	 * @param list<string>|null $objectSearchAny Case-insensitive substrings
	 *                                           scoped to the object, of which
	 *                                           at least one must match (OR
	 *                                           within the list, AND with
	 *                                           everything else, including
	 *                                           $objectSearch). Lets a consumer
	 *                                           match a set of name patterns
	 *                                           in one query; an empty list
	 *                                           matches nothing.
	 * @param list<string>|null $initiatorIds Exact-match initiator ids (IN);
	 *                                        an empty list matches nothing.
	 * @param list<string>|null $recipientIds Exact-match recipient ids (IN);
	 *                                        group, team and other container
	 *                                        ids are allowed; an empty list
	 *                                        matches nothing.
	 * @param list<string>|null $permissionIds ANY-of filter on opaque
	 *                                         {@see ShareReviewPermission::$id}
	 *                                         values: a share matches if it
	 *                                         grants at least one of them. Ids
	 *                                         of a foreign namespace — and an
	 *                                         empty list — match nothing.
	 * @param list<string>|null $tokens Exact-match access tokens (IN) of any
	 *                                  share type, for looking up the share a
	 *                                  known link belongs to; an empty list,
	 *                                  and every share of a source that has no
	 *                                  tokens, match nothing. Tokens are bearer
	 *                                  credentials: a source MUST compare them
	 *                                  exactly — never as a substring or
	 *                                  prefix, which would let a caller
	 *                                  discover a token it does not have — and
	 *                                  MUST NOT match the token column for any
	 *                                  other filter unless the token is the
	 *                                  share's recipient (link shares).
	 *
	 * @throws \InvalidArgumentException on an out-of-range $limit or $offset
	 *                                   or an unknown $sortField
	 *
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly int $limit = 100,
		public readonly int $offset = 0,
		public readonly ?string $search = null,
		public readonly string $sortField = self::SORT_TIME,
		public readonly bool $sortDescending = true,
		public readonly ?int $modifiedSinceTimestamp = null,
		public readonly ?array $shareTypes = null,
		public readonly ?bool $hasPassword = null,
		public readonly ?bool $hasExpiration = null,
		public readonly ?int $expiresAfterTimestamp = null,
		public readonly ?int $expiresBeforeTimestamp = null,
		public readonly ?string $initiatorSearch = null,
		public readonly ?string $recipientSearch = null,
		public readonly ?string $objectSearch = null,
		public readonly ?array $objectSearchAny = null,
		public readonly ?array $initiatorIds = null,
		public readonly ?array $recipientIds = null,
		public readonly ?array $permissionIds = null,
		public readonly ?array $tokens = null,
	) {
		if ($limit < 1 || $limit > self::MAX_LIMIT) {
			throw new \InvalidArgumentException('limit must be between 1 and ' . self::MAX_LIMIT);
		}
		if ($offset < 0) {
			throw new \InvalidArgumentException('offset must not be negative');
		}
		if (!in_array($sortField, self::SORTABLE_FIELDS, true)) {
			throw new \InvalidArgumentException('Unknown sort field');
		}
	}

	/**
	 * Whether any search or filter narrows the result. When this is false a
	 * source can skip the filtered count, as it equals the total count.
	 *
	 * @since 36.0.0
	 */
	public function isFiltered(): bool {
		return $this->search !== null
			|| $this->modifiedSinceTimestamp !== null
			|| $this->shareTypes !== null
			|| $this->hasPassword !== null
			|| $this->hasExpiration !== null
			|| $this->expiresAfterTimestamp !== null
			|| $this->expiresBeforeTimestamp !== null
			|| $this->initiatorSearch !== null
			|| $this->recipientSearch !== null
			|| $this->objectSearch !== null
			|| $this->objectSearchAny !== null
			|| $this->initiatorIds !== null
			|| $this->recipientIds !== null
			|| $this->permissionIds !== null
			|| $this->tokens !== null;
	}
}
