<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace NCU\Security\Signature\Model;

use NCU\Security\Signature\ISignatoryManager;

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
 */
interface ISignatory {
	/**
	 * unique string, related to the ISignatoryManager
	 *
	 * @see ISignatoryManager::getProviderId
	 * @param string $providerId
	 *
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setProviderId(string $providerId): ISignatory;

	/**
	 * returns the provider id, unique string related to the ISignatoryManager
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getProviderId(): string;

	/**
	 * set account, in case your ISignatoryManager needs to manage multiple keys from same host
	 *
	 * @param string $account
	 *
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setAccount(string $account): ISignatory;

	/**
	 * return account name, empty string if not set
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getAccount(): string;

	/**
	 * returns key id
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getKeyId(): string;

	/**
	 * returns public key
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getPublicKey(): string;

	/**
	 * returns private key, if available
	 *
	 * @return string
	 * @since 31.0.0
	 */
	public function getPrivateKey(): string;

	/**
	 * set metadata
	 *
	 * @param array $metadata
	 *
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setMetadata(array $metadata): ISignatory;

	/**
	 * returns metadata
	 *
	 * @return array
	 * @since 31.0.0
	 */
	public function getMetadata(): array;

	/**
	 * update an entry in metadata
	 *
	 * @param string $key
	 * @param string|int $value
	 *
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setMetaValue(string $key, string|int $value): ISignatory;

	/**
	 * set SignatoryType
	 *
	 * @param SignatoryType $type
	 *
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setType(SignatoryType $type): ISignatory;

	/**
	 * returns SignatoryType
	 *
	 * @return SignatoryType
	 * @since 31.0.0
	 */
	public function getType(): SignatoryType;

	/**
	 * set SignatoryStatus
	 *
	 * @param SignatoryStatus $status
	 *
	 * @see SignatoryStatus
	 * @return ISignatory
	 * @since 31.0.0
	 */
	public function setStatus(SignatoryStatus $status): ISignatory;

	/**
	 * get SignatoryStatus
	 *
	 * @see SignatoryStatus
	 * @return SignatoryStatus
	 * @since 31.0.0
	 */
	public function getStatus(): SignatoryStatus;

	/**
	 * get last timestamp this entry has been updated
	 *
	 * @return int
	 * @since 31.0.0
	 */
	public function getLastUpdated(): int;
}
