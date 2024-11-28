<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

use JsonSerializable;
use NCU\Security\Signature\Enum\SignatoryStatus;
use NCU\Security\Signature\Enum\SignatoryType;
use OCP\AppFramework\Db\Entity;

/**
 * model that store keys and details related to host and in use protocol
 * mandatory details are providerId, host, keyId and public key.
 * private key is only used for local signatory, used to sign outgoing request
 *
 * the pair providerId+host is unique, meaning only one signatory can exist for each host
 * and protocol
 *
 * @since 31.0.0
 * @experimental 31.0.0
 *
 * @method void setProviderId(string $providerId)
 * @method string getProviderId()
 * @method string getKeyId()
 * @method void setPublicKey(string $publicKey)
 * @method string getPublicKey()
 * @method void setPrivateKey(string $privateKey)
 * @method string getPrivateKey()
 * @method void setHost(string $host)
 * @method string getHost()
 * @method void setAccount(string $account)
 * @method string getAccount()
 * @method void setMetadata(array $metadata)
 * @method array getMetadata()
 * @method void setCreation(int $creation)
 * @method int getCreation()
 * @method void setLastUpdated(int $creation)
 * @method int getLastUpdated()
 */
class Signatory extends Entity implements JsonSerializable {
	protected string $keyId = '';
	protected string $keyIdSum = '';
	protected string $providerId = '';
	protected string $host = '';
	protected string $account = '';
	protected int $type = 9;
	protected int $status = 1;
	protected array $metadata = [];
	protected int $creation = 0;
	protected int $lastUpdated = 0;

	/**
	 * @param string $keyId
	 * @param string $publicKey
	 * @param string $privateKey
	 * @param bool $local
	 *
	 * @since 31.0.0
	 */
	public function __construct(
		string $keyId = '',
		protected string $publicKey = '',
		protected string $privateKey = '',
		private readonly bool $local = false,
	) {
		$this->addType('providerId', 'string');
		$this->addType('host', 'string');
		$this->addType('account', 'string');
		$this->addType('keyId', 'string');
		$this->addType('keyIdSum', 'string');
		$this->addType('publicKey', 'string');
		$this->addType('metadata', 'json');
		$this->addType('type', 'integer');
		$this->addType('status', 'integer');
		$this->addType('creation', 'integer');
		$this->addType('lastUpdated', 'integer');

		$this->setKeyId($keyId);
	}

	/**
	 * @param string $keyId
	 *
	 * @since 31.0.0
	 */
	public function setKeyId(string $keyId): void {
		// if set as local (for current instance), we apply some filters.
		if ($this->local) {
			// to avoid conflict with duplicate key pairs (ie generated url from the occ command), we enforce https as prefix
			if (str_starts_with($keyId, 'http://')) {
				$keyId = 'https://' . substr($keyId, 7);
			}

			// removing /index.php from generated url
			$path = parse_url($keyId, PHP_URL_PATH);
			if (str_starts_with($path, '/index.php/')) {
				$pos = strpos($keyId, '/index.php');
				if ($pos !== false) {
					$keyId = substr_replace($keyId, '', $pos, 10);
				}
			}
		}
		$this->keyId = $keyId;
		$this->keyIdSum = hash('sha256', $keyId);
	}

	/**
	 * @param SignatoryType $type
	 * @since 31.0.0
	 */
	public function setType(SignatoryType $type): void {
		$this->type = $type->value;
	}

	/**
	 * @return SignatoryType
	 * @since 31.0.0
	 */
	public function getType(): SignatoryType {
		return SignatoryType::from($this->type);
	}

	/**
	 * @param SignatoryStatus $status
	 * @since 31.0.0
	 */
	public function setStatus(SignatoryStatus $status): void {
		$this->status = $status->value;
	}

	/**
	 * @return SignatoryStatus
	 * @since 31.0.0
	 */
	public function getStatus(): SignatoryStatus {
		return SignatoryStatus::from($this->status);
	}

	/**
	 * update an entry in metadata
	 *
	 * @param string $key
	 * @param string|int|float|bool|array $value
	 * @since 31.0.0
	 */
	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
		$this->metadata[$key] = $value;
	}

	/**
	 * @return array
	 * @since 31.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'keyId' => $this->getKeyId(),
			'publicKeyPem' => $this->getPublicKey()
		];
	}
}
