<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use OCP\AppFramework\Attribute\Implementable;

/**
 * For actors whose authority comes from the instance's configuration rather
 * than from an account: cron jobs, `occ` commands without a user option,
 * system-wide bots.
 *
 * Narrow to this for a check meaning "trusted internal caller, per-user checks
 * do not apply".
 *
 * May be combined with {@see IIdentifiableActor} by implementations that carry
 * an identifier without having an account.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISystemActor extends IActor {
}
