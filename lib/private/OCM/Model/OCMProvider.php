<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function setEnabled(bool $enabled): static {
		$this->enabled = $enabled;

		return $this;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function setApiVersion(string $apiVersion): static {
		$this->apiVersion = $apiVersion;

		return $this;
	}

	public function getApiVersion(): string {
		return $this->apiVersion;
	}

	public function setEndPoint(string $endPoint): static {
		$this->endPoint = $endPoint;

		return $this;
	}

	public function getEndPoint(): string {
		return $this->endPoint;
	}

	public function createNewResourceType(): IOCMResource {
		return new OCMResource();
	}

	public function addResourceType(IOCMResource $resource): static {
		$this->resourceTypes[] = $resource;

		return $this;
	}

	public function setResourceTypes(array $resourceTypes): static {
		$this->resourceTypes = $resourceTypes;

		return $this;
	}

	public function getResourceTypes(): array {
		if (!$this->emittedEvent) {
			$this->emittedEvent = true;
			$event = new ResourceTypeRegisterEvent($this);
			$this->dispatcher->dispatchTyped($event);
		}

		return $this->resourceTypes;
	}

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
