<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Exception;

use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use OCP\Server;

/**
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class ShareNotFoundException extends AShareException {
	/**
	 * @since 35.0.0
	 */
	public function __construct() {
		parent::__construct('Share not found.', Server::get(IFactory::class)->get('sharing')->t('Share not found.'));
	}
}
