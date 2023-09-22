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

use OC\OCM\Model\OCMResource;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;

/**
 * Model based on the Open Cloud Mesh Discovery API
 * @link https://github.com/cs3org/OCM-API/
 * @since 28.0.0
 */
interface IOCMProvider {
	/**
	 * enable OCM
	 *
	 * @param bool $enabled
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setEnabled(bool $enabled): self;

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
	 * @return self
	 * @since 28.0.0
	 */
	public function setApiVersion(string $apiVersion): self;

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
	 * @return self
	 * @since 28.0.0
	 */
	public function setEndPoint(string $endPoint): self;

	/**
	 * get configured endpoint
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getEndPoint(): string;

	/**
	 * add a single resource to the object
	 *
	 * @param OCMResource $resource
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function addResourceType(OCMResource $resource): self;

	/**
	 * set resources
	 *
	 * @param OCMResource[] $resourceTypes
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setResourceTypes(array $resourceTypes): self;

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
	 * @return self
	 * @throws OCMProviderException in case a descent provider cannot be generated from data
	 * @since 28.0.0
	 */
	public function import(array $data): self;
}
