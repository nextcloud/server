<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Share\External;

use OCP\AppFramework\Attribute\Consumable;


/**
*
* This interface allows to represent an external share object
*
* @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IExternalShare {
}
