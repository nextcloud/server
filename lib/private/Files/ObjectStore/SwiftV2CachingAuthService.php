<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use OpenStack\Common\Auth\Token;
use OpenStack\Identity\v2\Service;

class SwiftV2CachingAuthService extends Service {
	public function authenticate(array $options = []): array {
		if (isset($options['v2cachedToken'], $options['v2serviceUrl'])
			&& $options['v2cachedToken'] instanceof Token
			&& is_string($options['v2serviceUrl'])) {
			return [$options['v2cachedToken'], $options['v2serviceUrl']];
		} else {
			return parent::authenticate($options);
		}
	}
}
