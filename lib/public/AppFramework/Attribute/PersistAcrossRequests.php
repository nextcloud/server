<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Marks a service as safe to keep alive in the server container across
 * multiple requests handled by the same long-running PHP process, such as
 * a FrankenPHP worker.
 *
 * Only use this on services that hold no request- or session-specific
 * state (no captured superglobals, no per-user data, no dependency on
 * services like {@see \OCP\IRequest} or {@see \OCP\ISession}).
 *
 * @since 36.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PersistAcrossRequests {
}
