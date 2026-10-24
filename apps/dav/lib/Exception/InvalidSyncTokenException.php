<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Exception;

/**
 * The remote server rejected the sync token of a sync-collection report
 * (DAV:valid-sync-token precondition), e.g. because its change history was
 * pruned. The collection has to be synced from scratch.
 */
class InvalidSyncTokenException extends \Exception {
}
