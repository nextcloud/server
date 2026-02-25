<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework;

use Psr\Http\Message\MessageInterface;

/**
 * A collection of helper methods to manipulate RequestInterface
 *
 * @since 34.0.0
 */
final class RequestHelper {
	public static function isUserAgent(MessageInterface $message, array $agent): bool {
		$userAgent = $message->getHeader('HTTP_USER_AGENT');
		if ($userAgent === []) {
			return false;
		}
		foreach ($agent as $regex) {
			if (preg_match($regex, $userAgent[0])) {
				return true;
			}
		}
		return false;
	}
}
