<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
