<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\EventDispatcher;

/**
 * Interface for events which can be listened to by webhooks
 *
 * @since 30.0.0
 */
interface IWebhookCompatibleEvent {
	/**
	 * Return data to be serialized and sent to the webhook. Will be serialized using json_encode.
	 *
	 * @since 30.0.0
	 */
	public function getWebhookSerializable(): array;
}
