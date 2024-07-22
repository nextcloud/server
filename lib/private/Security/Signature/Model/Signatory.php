<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Signature\Model;

use JsonSerializable;
use OCP\Security\Signature\Model\ISignatory;
use OCP\Security\Signature\Model\SignatoryStatus;
use OCP\Security\Signature\Model\SignatoryType;

class Signatory implements ISignatory, JsonSerializable {
	private string $providerId = '', $account = '';
	private SignatoryType $type = SignatoryType::STATIC;
	private SignatoryStatus $status = SignatoryStatus::SYNCED;
	private array $metadata = [];
	private int $creation = 0, $lastUpdated = 0;

	public function __construct(
		private string $keyId,
		private readonly string $publicKey,
		private readonly string $privateKey = '',
		readonly bool $local = false
	) {
		// if set as local (for current instance), we apply some filters.
		if ($local) {
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

			$this->keyId = $keyId;
		}
	}

	public function setProviderId(string $providerId): self {
		$this->providerId = $providerId;
		return $this;
	}

	public function getProviderId(): string {
		return $this->providerId;
	}

	public function setAccount(string $account): self {
		$this->account = $account;
		return $this;
	}

	public function getAccount(): string {
		return $this->account;
	}

	public function getKeyId(): string {
		return $this->keyId;
	}

	public function getPublicKey(): string {
		return $this->publicKey;
	}

	public function getPrivateKey(): string {
		return $this->privateKey;
	}

	public function setMetadata(array $metadata): self {
		$this->metadata = $metadata;
		return $this;
	}

	public function getMetadata(): array {
		return $this->metadata;
	}

	public function setMetaValue(string $key, string|int $value): self {
		$this->metadata[$key] = $value;
		return $this;
	}

	public function setType(SignatoryType $type): self {
		$this->type = $type;
		return $this;
	}
	public function getType(): SignatoryType {
		return $this->type;
	}

	public function setStatus(SignatoryStatus $status): self {
		$this->status = $status;
		return $this;
	}

	public function getStatus(): SignatoryStatus {
		return $this->status;
	}

	public function setCreation(int $creation): self {
		$this->creation = $creation;
		return $this;
	}

	public function getCreation(): int {
		return $this->creation;
	}

	public function setLastUpdated(int $lastUpdated): self {
		$this->lastUpdated = $lastUpdated;
		return $this;
	}

	public function getLastUpdated(): int {
		return $this->lastUpdated;
	}

	public function importFromDatabase(array $row): self {
		$this->setProviderId($row['provider_id'] ?? '')
			 ->setAccount($row['account'] ?? '')
			 ->setMetadata($row['metadata'] ?? [])
			 ->setType(SignatoryType::from($row['type'] ?? 9))
			 ->setStatus(SignatoryStatus::from($row['status'] ?? 1))
			 ->setCreation($row['creation'] ?? 0)
			 ->setLastUpdated($row['last_updated'] ?? 0);
		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'keyId' => $this->getKeyId(),
			'publicKeyPem' => $this->getPublicKey()
		];
	}
}
