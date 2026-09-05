<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Resource;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Names a kind of resource an API or function call can be about: a Talk room,
 * a Tables row, a file version. Resource types are open - every app defines
 * its own.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IResource {
}
