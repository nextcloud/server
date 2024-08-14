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
	 * returns the capabilities of the API
	 *
	 * @return array
	 * @since 30.0.0
	 */
	public function getCapabilities(): array;

	/**
	 * set the capabilities of the API
	 *
	 * @param array $capabilities
	 *
	 * @return $this
	 * @since 30.0.0
	 */

	public function setCapabilities(array $capabilities): static;

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
	 * get provider
	 *
	 * @return string
	 * @since 30.0.0
	 */
	public function getProvider()): string;
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
	 *     apiVersion: string,
	 *     endPoint: string,
	 *     resourceTypes: array{
	 *         name: string,
	 *         shareTypes: string[],
	 *         protocols: array<string, string>
	 *     }[]
	 * }
	 * @since 28.0.0
	 */
	public function jsonSerialize(): array;
}
