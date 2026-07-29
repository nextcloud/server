<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Recipient;

use NCU\Sharing\ShareAccessContext;
use OCP\AppFramework\Attribute\Implementable;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareRecipientTypeSearch extends IShareRecipientType {
	/**
	 * Search for recipients.
	 *
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<ShareRecipient>
	 * @experimental 35.0.0
	 */
	public function searchRecipients(ShareAccessContext $accessContext, string $query, int $limit, int $offset): array;
}
