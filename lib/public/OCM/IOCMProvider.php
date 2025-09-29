<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\OCM;

use JsonSerializable;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;

/**
 * Model based on the Open Cloud Mesh Discovery API
 * @link https://github.com/cs3org/OCM-API/
 * @since 28.0.0
 * @deprecated 32.0.0 Please use {@see \OCP\OCM\ICapabilityAwareOCMProvider}
 */
interface IOCMProvider extends JsonSerializable {
	/**
	 * enable OCM
	 *
	 * @param bool $enabled
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setEnabled(bool $enabled): static;

	/**
	 * is set as enabled ?
	 *
	 * @return bool
	 * @since 28.0.0
	 */
	public function isEnabled(): bool;

	/**
	 * get set API Version
	 *
	 * @param string $apiVersion
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setApiVersion(string $apiVersion): static;

	/**
	 * returns API version
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getApiVersion(): string;

	/**
	 * configure endpoint
	 *
	 * @param string $endPoint
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setEndPoint(string $endPoint): static;

	/**
	 * get configured endpoint
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getEndPoint(): string;

	/**
	 * create a new resource to later add it with {@see addResourceType()}
	 * @return IOCMResource
	 * @since 28.0.0
	 */
	public function createNewResourceType(): IOCMResource;

	/**
	 * add a single resource to the object
	 *
	 * @param IOCMResource $resource
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function addResourceType(IOCMResource $resource): static;

	/**
	 * set resources
	 *
	 * @param IOCMResource[] $resourceTypes
	 *
	 * @return $this
	 * @since 28.0.0
	 */
	public function setResourceTypes(array $resourceTypes): static;

	/**
	 * get all set resources
	 *
	 * @return IOCMResource[]
	 * @since 28.0.0
	 */
	public function getResourceTypes(): array;

	/**
	 * extract a specific string value from the listing of protocols, based on resource-name and protocol-name
	 *
	 * @param string $resourceName
	 * @param string $protocol
	 *
	 * @return string
	 * @throws OCMArgumentException
	 * @since 28.0.0
	 */
	public function extractProtocolEntry(string $resourceName, string $protocol): string;

	//	/**
	//	 * store signatory (public/private key pair) to sign outgoing/incoming request
	//	 *
	//	 * @param Signatory $signatory
	//	 * @experimental 31.0.0
	//	 */
	//	public function setSignatory(Signatory $signatory): void;

	//	/**
	//	 * signatory (public/private key pair) used to sign outgoing/incoming request
	//	 *
	//	 * @return Signatory|null returns null if no Signatory available
	//	 * @experimental 31.0.0
	//	 */
	//	public function getSignatory(): ?Signatory;

	/**
	 * import data from an array
	 *
	 * @param array<string, int|string|bool|array> $data
	 *
	 * @return $this
	 * @throws OCMProviderException in case a descent provider cannot be generated from data
	 * @since 28.0.0
	 */
	public function import(array $data): static;

	/**
	 * @return array{
	 *     enabled: bool,
	 *     apiVersion: '1.0-proposal1',
	 *     endPoint: string,
	 *     publicKey?: array{
	 *         keyId: string,
	 *         publicKeyPem: string
	 *	   },
	 *     resourceTypes: list<array{
	 *         name: string,
	 *         shareTypes: list<string>,
	 *         protocols: array<string, string>
	 *     }>,
	 *     version: string
	 * }
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array;
}
