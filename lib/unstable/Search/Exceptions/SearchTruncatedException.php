<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Search\Exceptions;

/**
 * The search matched more than the provider can answer exhaustively.
 *
 * @experimental 36.0.0
 */
final class SearchTruncatedException extends \RuntimeException {
}
