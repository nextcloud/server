<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Sharing\ShareAccessContext;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IShareRecipientTypeSearch extends IShareRecipientType {
	/**
	 * Search for recipients.
	 *
	 * @param non-empty-string $query
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 */
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array;
}
