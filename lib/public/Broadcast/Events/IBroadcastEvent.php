<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Broadcast\Events;

use JsonSerializable;

/**
 * @since 18.0.0
 */
interface IBroadcastEvent {
	/**
	 * @return string the name of the event
	 * @since 18.0.0
	 */
	public function getName(): string;

	/**
	 * @return string[]
	 * @since 18.0.0
	 */
	public function getUids(): array;

	/**
	 * @return JsonSerializable the data to be sent to the client
	 * @since 18.0.0
	 */
	public function getPayload(): JsonSerializable;

	/**
	 * @since 18.0.0
	 */
	public function setBroadcasted(): void;
}
