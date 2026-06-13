<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Notification;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Allow notifier implementations to preload and cache data for many notifications at once to
 * improve performance by, for example, bundling SQL queries.
 */
#[Implementable(since: '32.0.0')]
interface IPreloadableNotifier extends INotifier {
	/**
	 * This method provides a way for notifier implementations to preload and cache data for many
	 * notifications. The data is meant to be consumed later in the {@see INotifier::prepare()}
	 * method to improve performance.
	 *
	 * @since 32.0.0
	 *
	 * @param INotification[] $notifications The notifications which are about to be prepared in the next step.
	 * @param string $languageCode The code of the language that should be used to prepare the notification.
	 * @param NotificationPreloadReason $reason The reason for preloading the given notifications to facilitate smarter decisions about what data to preload.
	 */
	public function preloadDataForParsing(
		array $notifications,
		string $languageCode,
		NotificationPreloadReason $reason,
	): void;
}
