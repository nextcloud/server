<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use OCP\Migration\Attributes\DataCleansing;

/**
 * Run the old migration Version24000Date20211210141942 again.
 */
#[DataCleansing(table: 'preferences', description: 'lowercase accounts email address')]
class Version32000Date20250620081925 extends Version24000Date20211210141942 {
}
