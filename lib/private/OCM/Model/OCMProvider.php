<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\OCM\Model;

use NCU\Security\Signature\Model\Signatory;
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
	private ?Signatory $signatory = null;
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

	public function setSignatory(Signatory $signatory): void {
		$this->signatory = $signatory;
	}

	public function getSignatory(): ?Signatory {
		return $this->signatory;
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
			// Fall back to old apiVersion for Nextcloud 30 compatibility
			->setApiVersion((string)($data['version'] ?? $data['apiVersion'] ?? ''))
			->setEndPoint($data['endPoint'] ?? '');

		$resources = [];
		foreach (($data['resourceTypes'] ?? []) as $resourceData) {
			$resource = new OCMResource();
			$resources[] = $resource->import($resourceData);
		}
		$this->setResourceTypes($resources);

		if (isset($data['publicKey'])) {
			// import details about the remote request signing public key, if available
			$signatory = new Signatory();
			$signatory->setKeyId($data['publicKey']['keyId'] ?? '');
			$signatory->setPublicKey($data['publicKey']['publicKeyPem'] ?? '');
			if ($signatory->getKeyId() !== '' && $signatory->getPublicKey() !== '') {
				$this->setSignatory($signatory);
			}
		}

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
	 *      enabled: bool,
	 *      apiVersion: '1.0-proposal1',
	 *      endPoint: string,
	 *      publicKey: array{
	 *          keyId: string,
	 *          publicKeyPem: string
	 *      },
	 *      resourceTypes: list<array{
	 *          name: string,
	 *          shareTypes: list<string>,
	 *          protocols: array<string, string>
	 *      }>,
	 *      version: string
	 *  }
	 */
	public function jsonSerialize(): array {
		$resourceTypes = [];
		foreach ($this->getResourceTypes() as $res) {
			$resourceTypes[] = $res->jsonSerialize();
		}

		return [
			'enabled' => $this->isEnabled(),
			'apiVersion' => '1.0-proposal1', // deprecated, but keep it to stay compatible with old version
			'version' => $this->getApiVersion(), // informative but real version
			'endPoint' => $this->getEndPoint(),
			'publicKey' => $this->getSignatory()->jsonSerialize(),
			'resourceTypes' => $resourceTypes
		];
	}
}
