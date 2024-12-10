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
use NCU\Security\Signature\Exceptions\IdentityNotFoundException;
use OCP\AppFramework\Db\Entity;

/**
 * model that store keys and details related to host and in use protocol
 * mandatory details are providerId, host, keyId and public key.
 * private key is only used for local signatory, used to sign outgoing request
 *
 * the pair providerId+host is unique, meaning only one signatory can exist for each host
 * and protocol
 *
 * @experimental 31.0.0
 *
 * @method void setProviderId(string $providerId)
 * @method string getProviderId()
 * @method string getKeyId()
 * @method void setKeyIdSum(string $keyIdSum)
 * @method string getKeyIdSum()
 * @method void setPublicKey(string $publicKey)
 * @method string getPublicKey()
 * @method void setPrivateKey(string $privateKey)
 * @method string getPrivateKey()
 * @method void setHost(string $host)
 * @method string getHost()
 * @method int getType()
 * @method void setType(int $type)
 * @method int getStatus()
 * @method void setStatus(int $status)
 * @method void setAccount(?string $account)
 * @method void setMetadata(array $metadata)
 * @method ?array getMetadata()
 * @method void setCreation(int $creation)
 * @method int getCreation()
 * @method void setLastUpdated(int $creation)
 * @method int getLastUpdated()
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Signatory extends Entity implements JsonSerializable {
	protected string $keyId = '';
	protected string $keyIdSum = '';
	protected string $providerId = '';
	protected string $host = '';
	protected string $publicKey = '';
	protected string $privateKey = '';
	protected ?string $account = '';
	protected int $type = 9;
	protected int $status = 1;
	protected ?array $metadata = null;
	protected int $creation = 0;
	protected int $lastUpdated = 0;

	/**
	 * @param bool $local only set to TRUE when managing local signatory
	 *
	 * @experimental 31.0.0
	 */
	public function __construct(
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
	}

	/**
	 * @param string $keyId
	 *
	 * @experimental 31.0.0
	 * @throws IdentityNotFoundException if identity cannot be extracted from keyId
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
		$this->setter('keyId', [$keyId]); // needed to trigger the update in database
		$this->setKeyIdSum(hash('sha256', $keyId));

		$this->setHost(self::extractIdentityFromUri($this->getKeyId()));
	}

	/**
	 * @param SignatoryType $type
	 * @experimental 31.0.0
	 */
	public function setSignatoryType(SignatoryType $type): void {
		$this->setType($type->value);
	}

	/**
	 * @return SignatoryType
	 * @experimental 31.0.0
	 */
	public function getSignatoryType(): SignatoryType {
		return SignatoryType::from($this->getType());
	}

	/**
	 * @param SignatoryStatus $status
	 * @experimental 31.0.0
	 */
	public function setSignatoryStatus(SignatoryStatus $status): void {
		$this->setStatus($status->value);
	}

	/**
	 * @return SignatoryStatus
	 * @experimental 31.0.0
	 */
	public function getSignatoryStatus(): SignatoryStatus {
		return SignatoryStatus::from($this->getStatus());
	}

	/**
	 * @experimental 31.0.0
	 */
	public function getAccount(): string {
		return $this->account ?? '';
	}

	/**
	 * update an entry in metadata
	 *
	 * @param string $key
	 * @param string|int|float|bool|array $value
	 * @experimental 31.0.0
	 */
	public function setMetaValue(string $key, string|int|float|bool|array $value): void {
		$this->metadata[$key] = $value;
		$this->setter('metadata', [$this->metadata]);
	}

	/**
	 * @return array
	 * @experimental 31.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'keyId' => $this->getKeyId(),
			'publicKeyPem' => $this->getPublicKey()
		];
	}

	/**
	 * static is needed to make this easily callable from outside the model
	 *
	 * @param string $uri
	 *
	 * @return string
	 * @throws IdentityNotFoundException if identity cannot be extracted
	 * @experimental 31.0.0
	 */
	public static function extractIdentityFromUri(string $uri): string {
		$identity = parse_url($uri, PHP_URL_HOST);
		$port = parse_url($uri, PHP_URL_PORT);
		if ($identity === null || $identity === false) {
			throw new IdentityNotFoundException('cannot extract identity from ' . $uri);
		}

		if ($port !== null && $port !== false) {
			$identity .= ':' . $port;
		}

		return $identity;
	}

}
