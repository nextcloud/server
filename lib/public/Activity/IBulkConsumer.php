<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Activity;

/**
 * Interface IBulkConsumer
 *
 * @since 32.0.0
 */
interface IBulkConsumer extends IConsumer {
	/**
	 * @param IEvent $event
	 * @param array $affectedUserIds
	 * @param ISetting $setting
	 * @return void
	 * @since 32.0.0
	 */
	public function bulkReceive(IEvent $event, array $affectedUserIds, ISetting $setting): void;
}
