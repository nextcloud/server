<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\Resource\Exception;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\Throwable;

/**
 * The resource does not exist, or exists but the actor cannot see it. The two
 * cases deliberately collapse together, so existence never leaks to an actor
 * without access.
 *
 * @experimental 36.0.0
 */
#[Throwable(since: '36.0.0')]
#[Catchable(since: '36.0.0')]
class ResourceNotFoundException extends \RuntimeException {
}
