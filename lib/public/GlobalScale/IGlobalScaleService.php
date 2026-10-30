<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\GlobalScale;

use OCP\IUser;

/**
 * Service related to sending data from instances in a global scale setup.
 *
 * This service is only available when the globalsiteselector application is enabled.
 *
 * @since 34.0.3
 */
interface IGlobalScaleService {
	/**
	 * Send a payload from the primary server to the secondary server.
	 *
	 * The payload will be signed and will expire automatically in 5 min. The payload can then
	 * be decoded on the receiving end with {@see IGlobalScaleService::decodePayload()}.
	 *
	 * @param non-empty-string $path
	 * @return string The url of the secondary
	 * @throws \Exception When unable to send the payload.
	 * @since 34.0.3
	 */
	public function sendToSecondary(IUser $user, string $path, array $payload): string;

	/**
	 * Decode a receiving payload.
	 *
	 * The payload is signed and will expire automatically in 5 min.
	 *
	 * @param non-empty-string $jwt The payload encoded as a JWT
	 * @return array The original payload as sent by {@see IGlobalScaleService::sendToSecondary}
	 * @since 34.0.3
	 */
	public function decodePayload(string $jwt): array;
}
