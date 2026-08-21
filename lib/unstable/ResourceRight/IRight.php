<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceRight;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Names an operation a caller may be allowed to perform on a resource.
 *
 * A marker: implementations are never instantiated, and travel as
 * `class-string<IRight>`. The vocabulary is open - an app with no equivalent
 * in {@see \NCU\ResourceRight\Rights} adds its own.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IRight {
}
