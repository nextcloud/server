<?php

declare(strict_types=1);

/**
 * @copyright 2023, Maxence Lange <maxence@artificial-owl.com>
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

namespace OC\OCM\Model;

use OCP\EventDispatcher\IEventDispatcher;
use OCP\OCM\Events\ResourceTypeRegisterEvent;
use OCP\OCM\Exceptions\OCMArgumentException;
use OCP\OCM\Exceptions\OCMProviderException;
use OCP\OCM\IOCMProvider;
use OCP\OCM\IOCMResource;

/**
 * @since 28.0.0
 */
class OCMProvider implements IOCMProvider {
	private bool $enabled = false;
	private string $apiVersion = '';
	private string $endPoint = '';
	/** @var IOCMResource[] */
	private array $resourceTypes = [];

	private bool $emittedEvent = false;

	public function __construct(
		protected IEventDispatcher $dispatcher,
	) {
	}

	/**
	 * @param bool $enabled
	 *
	 * @return $this
	 */
	public function setEnabled(bool $enabled): static {
		$this->enabled = $enabled;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isEnabled(): bool {
		return $this->enabled;
	}

	/**
	 * @param string $apiVersion
	 *
	 * @return $this
	 */
	public function setApiVersion(string $apiVersion): static {
		$this->apiVersion = $apiVersion;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getApiVersion(): string {
		return $this->apiVersion;
	}

	/**
	 * @param string $endPoint
	 *
	 * @return $this
	 */
	public function setEndPoint(string $endPoint): static {
		$this->endPoint = $endPoint;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEndPoint(): string {
		return $this->endPoint;
	}

	/**
	 * create a new resource to later add it with {@see IOCMProvider::addResourceType()}
	 * @return IOCMResource
	 */
	public function createNewResourceType(): IOCMResource {
		return new OCMResource();
	}

	/**
	 * @param IOCMResource $resource
	 *
	 * @return $this
	 */
	public function addResourceType(IOCMResource $resource): static {
		$this->resourceTypes[] = $resource;

		return $this;
	}

	/**
	 * @param IOCMResource[] $resourceTypes
	 *
	 * @return $this
	 */
	public function setResourceTypes(array $resourceTypes): static {
		$this->resourceTypes = $resourceTypes;

		return $this;
	}

	/**
	 * @return IOCMResource[]
	 */
	public function getResourceTypes(): array {
		if (!$this->emittedEvent) {
			$this->emittedEvent = true;
			$event = new ResourceTypeRegisterEvent($this);
			$this->dispatcher->dispatchTyped($event);
		}

		return $this->resourceTypes;
	}

	/**
	 * @param string $resourceName
	 * @param string $protocol
	 *
	 * @return string
	 * @throws OCMArgumentException
	 */
	public function extractProtocolEntry(string $resourceName, string $protocol): string {
		foreach ($this->getResourceTypes() as $resource) {
			if ($resource->getName() === $resourceName) {
				$entry = $resource->getProtocols()[$protocol] ?? null;
				if (is_null($entry)) {
					throw new OCMArgumentException('protocol not found');
				}

				return (string)$entry;
			}
		}

		throw new OCMArgumentException('resource not found');
	}

	/**
	 * import data from an array
	 *
	 * @param array $data
	 *
	 * @return $this
	 * @throws OCMProviderException in case a descent provider cannot be generated from data
	 * @see self::jsonSerialize()
	 */
	public function import(array $data): static {
		$this->setEnabled(is_bool($data['enabled'] ?? '') ? $data['enabled'] : false)
			 ->setApiVersion((string)($data['apiVersion'] ?? ''))
			 ->setEndPoint($data['endPoint'] ?? '');

		$resources = [];
		foreach (($data['resourceTypes'] ?? []) as $resourceData) {
			$resource = new OCMResource();
			$resources[] = $resource->import($resourceData);
		}
		$this->setResourceTypes($resources);

		if (!$this->looksValid()) {
			throw new OCMProviderException('remote provider does not look valid');
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	private function looksValid(): bool {
		return ($this->getApiVersion() !== '' && $this->getEndPoint() !== '');
	}


	/**
	 * @return array{
	 *     enabled: bool,
	 *     apiVersion: string,
	 *     endPoint: string,
	 *     resourceTypes: array{
	 *              name: string,
	 *              shareTypes: string[],
	 *              protocols: array<string, string>
	 *            }[]
	 *   }
	 */
	public function jsonSerialize(): array {
		$resourceTypes = [];
		foreach ($this->getResourceTypes() as $res) {
			$resourceTypes[] = $res->jsonSerialize();
		}

		return [
			'enabled' => $this->isEnabled(),
			'apiVersion' => $this->getApiVersion(),
			'endPoint' => $this->getEndPoint(),
			'resourceTypes' => $resourceTypes
		];
	}
}
