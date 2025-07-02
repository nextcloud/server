<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Federation\Db;

use JsonSerializable;
use OCA\Federation\TrustedServers;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUrl()
 * @method void setUrl(string $url)
 * @method string getUrlHash()
 * @method void setUrlHash(string $urlHash)
 * @method string|null getToken()
 * @method void setToken(?string $token)
 * @method string|null getSharedSecret()
 * @method void setSharedSecret(?string $sharedSecret)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method string|null getSyncToken()
 * @method void setSyncToken(?string $syncToken)
 *
 * @psalm-type TrustedServerStatus = TrustedServers::STATUS_OK|TrustedServers::STATUS_PENDING|TrustedServers::STATUS_FAILURE|TrustedServers::STATUS_ACCESS_REVOKED
 */
class TrustedServer extends Entity implements JsonSerializable {

	protected string $url;
	protected string $urlHash;
	protected ?string $token;
	protected ?string $sharedSecret;
	/** @psalm-var TrustedServerStatus $status */
	protected int $status;
	protected ?string $syncToken;

	public function __construct() {
		$this->addType('url', 'string');
		$this->addType('urlHash', 'string');
		$this->addType('token', 'string');
		$this->addType('sharedSecret', 'string');
		$this->addType('status', 'integer');
		$this->addType('syncToken', 'string');
	}

	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'url' => $this->getUrl(),
			'urlHash' => $this->getUrlHash(),
			'token' => $this->getToken(),
			'sharedSecret' => $this->getSharedSecret(),
			'status' => $this->getStatus(),
			'syncToken' => $this->getSyncToken(),
		];
	}
}
