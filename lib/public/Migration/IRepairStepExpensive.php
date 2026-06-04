<?php

declare(strict_types=1);
/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration;

/**
 * Expensive repair steps are non-critical repair steps that might take a long time to execute.
 * Non-critical means that they are not required to directly be executed during migration to have a working instance,
 * but they might be required to have a fully working instance later on.
 *
 * Expensive repair steps are only executed when explicitly requested by the administrator.
 *
 * @since 34.0.0
 */
interface IRepairStepExpensive extends IRepairStep {
}
