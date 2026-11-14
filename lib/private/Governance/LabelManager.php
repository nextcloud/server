<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Governance;

use InvalidArgumentException;
use NCU\Governance\Events\LabelAssignedEvent;
use NCU\Governance\Events\LabelRemovedEvent;
use NCU\Governance\Exception\GovernanceUnavailableException;
use NCU\Governance\Exception\ResourceNotFoundException;
use NCU\Governance\ILabelManager;
use NCU\Governance\LabelType;
use NCU\Resource\Permission\IAccessManager;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Delegates to the governance app after checking the access of the actor to the resources.
 */
final class LabelManager implements ILabelManager {
	public const string APP_ID = 'governance';
	public const string BACKEND = 'OCA\\Governance\\Service\\LabelManager';

	private ?ILabelManager $backend = null;

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly IAccessManager $accessManager,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function isAvailable(): bool {
		return $this->getBackend()?->isAvailable() ?? false;
	}

	#[\Override]
	public function getAssignedLabelsForResources(Actor $actor, array $resources): array {
		$accessible = $this->filterAccessible($actor, $resources);

		$labels = [];
		foreach ($resources as $resource) {
			$labels[$resource->key()] = [];
		}

		$backend = $this->getBackend();
		if ($backend === null || $accessible === []) {
			return $labels;
		}

		foreach ($backend->getAssignedLabelsForResources($actor, $accessible) as $resourceKey => $resourceLabels) {
			if (isset($labels[$resourceKey])) {
				$labels[$resourceKey] = array_values($resourceLabels);
			}
		}

		return $labels;
	}

	#[\Override]
	public function getAssignedLabels(Actor $actor, ResourceReference $resource): array {
		return $this->getAssignedLabelsForResources($actor, [$resource])[$resource->key()];
	}

	#[\Override]
	public function getAssignableLabels(Actor $actor, ResourceReference $resource, ?LabelType $type = null): array {
		$accessible = $this->filterAccessible($actor, [$resource]);
		$backend = $this->getBackend();
		if ($backend === null || $accessible === []) {
			return [];
		}

		return $backend->getAssignableLabels($actor, $resource, $type);
	}

	#[\Override]
	public function assignLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
		$this->change($actor, $resource, static fn (ILabelManager $backend) => $backend->assignLabel($actor, $resource, $labelId, $reason));
	}

	#[\Override]
	public function removeLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
		$this->change($actor, $resource, static fn (ILabelManager $backend) => $backend->removeLabel($actor, $resource, $labelId, $reason));
	}

	/**
	 * Run the change and dispatch an event for every label that is no longer or newly assigned afterwards.
	 *
	 * @param \Closure(ILabelManager): void $change
	 * @throws InvalidArgumentException
	 * @throws GovernanceUnavailableException
	 * @throws ResourceNotFoundException
	 */
	private function change(Actor $actor, ResourceReference $resource, \Closure $change): void {
		$accessible = $this->filterAccessible($actor, [$resource]);
		$backend = $this->getBackend();
		if ($backend === null) {
			throw new GovernanceUnavailableException('The governance app is not enabled');
		}
		if ($accessible === []) {
			throw new ResourceNotFoundException('The resource ' . $resource->key() . ' does not exist or cannot be accessed');
		}

		$before = $this->getAssignedTypes($backend, $actor, $resource);
		$change($backend);
		$after = $this->getAssignedTypes($backend, $actor, $resource);

		foreach (array_diff_key($before, $after) as $labelId => $labelType) {
			$this->eventDispatcher->dispatchTyped(new LabelRemovedEvent($actor, $resource, (string)$labelId, $labelType));
		}
		foreach (array_diff_key($after, $before) as $labelId => $labelType) {
			$this->eventDispatcher->dispatchTyped(new LabelAssignedEvent($actor, $resource, (string)$labelId, $labelType));
		}
	}

	/**
	 * @return array<array-key, LabelType> types of the assigned labels by label ID, numeric IDs are integer keys
	 */
	private function getAssignedTypes(ILabelManager $backend, Actor $actor, ResourceReference $resource): array {
		$types = [];
		foreach ($backend->getAssignedLabels($actor, $resource) as $label) {
			$types[$label->id] = $label->type;
		}

		return $types;
	}

	/**
	 * @param list<ResourceReference> $resources
	 * @return list<ResourceReference> the resources the actor has access to
	 * @throws InvalidArgumentException if a resource type is not registered
	 */
	private function filterAccessible(Actor $actor, array $resources): array {
		$access = $this->accessManager->getAccess($actor, $resources);

		$accessible = [];
		foreach ($resources as $resource) {
			if (isset($access[$resource->key()])) {
				$accessible[$resource->key()] = $resource;
			}
		}

		return array_values($accessible);
	}

	private function getBackend(): ?ILabelManager {
		if ($this->backend !== null) {
			return $this->backend;
		}
		if (!$this->appManager->isEnabledForAnyone(self::APP_ID)) {
			return null;
		}

		try {
			$backend = $this->container->get(self::BACKEND);
		} catch (Throwable $e) {
			$this->logger->error('Could not load the label manager of the governance app', ['exception' => $e]);
			return null;
		}
		if (!$backend instanceof ILabelManager) {
			$this->logger->error('{class} does not implement {interface}', ['class' => self::BACKEND, 'interface' => ILabelManager::class]);
			return null;
		}

		$this->backend = $backend;

		return $backend;
	}
}
