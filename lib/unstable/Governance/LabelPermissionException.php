<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\Throwable;

/**
 * The actor may not assign or remove this label on this resource.
 *
 * @experimental 36.0.0
 */
#[Throwable(since: '36.0.0')]
#[Catchable(since: '36.0.0')]
class LabelPermissionException extends \RuntimeException {
}
