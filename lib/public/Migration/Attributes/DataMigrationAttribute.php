<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use OCP\AppFramework\Attribute\Consumable;

/**
 * generic class related to migration attribute about data migration
 */
#[Consumable(since: '32.0.0')]
class DataMigrationAttribute extends MigrationAttribute {
}
