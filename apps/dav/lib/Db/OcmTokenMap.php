<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * Maps a short-lived OCM access token (by its oc_authtoken id) to the
 * long-lived refresh token it was issued for.
 *
 * @method int getAccessTokenId()
 * @method void setAccessTokenId(int $id)
 * @method string getRefreshToken()
 * @method void setRefreshToken(string $token)
 * @method int getExpires()
 * @method void setExpires(int $expires)
 */
class OcmTokenMap extends Entity {
	/** @var int ID of the access token row in oc_authtoken */
	protected $accessTokenId;

	/** @var string The refresh token this access token was issued for */
	protected $refreshToken;

	/** @var int Unix timestamp when the access token expires */
	protected $expires;

	public function __construct() {
		$this->addType('accessTokenId', Types::INTEGER);
		$this->addType('refreshToken', Types::STRING);
		$this->addType('expires', Types::INTEGER);
	}
}
