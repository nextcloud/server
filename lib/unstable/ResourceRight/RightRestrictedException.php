<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceRight;

use OCP\AppFramework\Attribute\Catchable;
use OCP\AppFramework\Attribute\Throwable;
use OCP\HintException;

/**
 * Thrown by the app that asked, once {@see RestrictRightEvent} reports a
 * restriction. Listeners never throw it.
 *
 * ```php
 * if ($hint = $event->isRestricted()) {
 *     throw new RightRestrictedException($hint, $hint);
 * }
 * ```
 *
 * @experimental 35.0.0
 */
#[Throwable(since: '35.0.0')]
#[Catchable(since: '35.0.0')]
final class RightRestrictedException extends HintException {
}
