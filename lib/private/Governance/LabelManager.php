<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Governance;

use InvalidArgumentException;
use NCU\Governance\Exception\GovernanceUnavailableException;
use NCU\Governance\ILabelManager;
use NCU\Governance\LabelType;
use NCU\Resource\Permission\IPermissionManager;
use NCU\Resource\ResourceReference;
use OCP\Actor\Actor;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Passes everything on to the governance app, which decides who may see and change a label.
 */
final class LabelManager implements ILabelManager {
	public const string APP_ID = 'governance';
	public const string BACKEND = 'OCA\\Governance\\Service\\LabelManager';

	private ?ILabelManager $backend = null;

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly IPermissionManager $permissionManager,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function isAvailable(): bool {
		return $this->getBackend()?->isAvailable() ?? false;
	}

	#[\Override]
	public function getAssignedLabelsForResources(Actor $actor, array $resources): array {
		$this->assertRegistered($resources);

		$labels = [];
		foreach ($resources as $resource) {
			$labels[$resource->key()] = [];
		}

		$backend = $this->getBackend();
		if ($backend === null || $resources === []) {
			return $labels;
		}

		foreach ($backend->getAssignedLabelsForResources($actor, $resources) as $resourceKey => $resourceLabels) {
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
		$this->assertRegistered([$resource]);

		return $this->getBackend()?->getAssignableLabels($actor, $resource, $type) ?? [];
	}

	#[\Override]
	public function assignLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
		$this->assertRegistered([$resource]);
		$this->getBackendOrThrow()->assignLabel($actor, $resource, $labelId, $reason);
	}

	#[\Override]
	public function removeLabel(Actor $actor, ResourceReference $resource, string $labelId, ?string $reason = null): void {
		$this->assertRegistered([$resource]);
		$this->getBackendOrThrow()->removeLabel($actor, $resource, $labelId, $reason);
	}

	/**
	 * @param list<ResourceReference> $resources
	 * @throws InvalidArgumentException if a resource type is not registered
	 */
	private function assertRegistered(array $resources): void {
		foreach ($resources as $resource) {
			if ($this->permissionManager->getResourceType($resource->type) === null) {
				throw new InvalidArgumentException('The resource type ' . $resource->type . ' is not registered');
			}
		}
	}

	/**
	 * @throws GovernanceUnavailableException
	 */
	private function getBackendOrThrow(): ILabelManager {
		return $this->getBackend() ?? throw new GovernanceUnavailableException('The governance app is not enabled');
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
