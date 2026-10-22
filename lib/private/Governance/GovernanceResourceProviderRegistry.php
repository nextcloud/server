<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Governance;

use NCU\Governance\IGovernanceBackend;
use NCU\Governance\IGovernanceResourceProviderRegistry;
use NCU\Governance\Resource\IGovernanceResourceProvider;
use RuntimeException;

final class GovernanceResourceProviderRegistry implements IGovernanceResourceProviderRegistry {
	private const GOVERNANCE_APP_ID = 'governance';

	/** @var class-string<IGovernanceBackend>|null */
	private ?string $backendClass = null;

	/** @var array<string, list<class-string<IGovernanceResourceProvider>>> */
	private array $resourceProviders = [];

	#[\Override]
	public function registerBackend(string $appId, string $class): void {
		if ($appId !== self::GOVERNANCE_APP_ID) {
			throw new RuntimeException('Only the Governance app is allowed to register a Governance backend');
		}
		if ($this->backendClass !== null) {
			throw new RuntimeException('There can only be one Governance backend');
		}

		$this->backendClass = $class;
	}

	#[\Override]
	public function registerResourceProvider(string $appId, string $class): void {
		$this->resourceProviders[$appId][] = $class;
	}

	#[\Override]
	public function getProviders(): array {
		return $this->resourceProviders;
	}

	/**
	 * @return class-string<IGovernanceBackend>|null
	 */
	public function getBackendClass(): ?string {
		return $this->backendClass;
	}
}
