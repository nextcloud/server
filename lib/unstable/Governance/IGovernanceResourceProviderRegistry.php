<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance;

use NCU\Governance\Resource\IGovernanceResourceProvider;
use OCP\AppFramework\Attribute\Consumable;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Registers the services Governance is built from. Call the register methods
 * from {@see IBootstrap::register()}.
 *
 * If you are looking for the Governance registry {@see IGovernanceManager::getRegistry()}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IGovernanceResourceProviderRegistry {
	/**
	 * Registers the Governance backend. Only the Governance app may register
	 * one, and only once.
	 *
	 * Will be moved to {@see IRegistrationContext}, which will then provide the app ID.
	 *
	 * @param string $appId ID of the app registering the backend
	 * @param class-string<IGovernanceBackend> $class
	 * @throws \RuntimeException when $appId is not the Governance app or a backend is already registered
	 * @experimental 36.0.0
	 */
	public function registerBackend(string $appId, string $class): void;

	/**
	 * Registers a resource provider.
	 *
	 * Will be moved to {@see IRegistrationContext}, which will then provide the app ID.
	 *
	 * @param string $appId ID of the app owning the resource
	 * @param class-string<IGovernanceResourceProvider> $class
	 * @experimental 36.0.0
	 */
	public function registerResourceProvider(string $appId, string $class): void;

	/**
	 * Internal use: every registered resource provider class, for the
	 * Governance app to build its registry from.
	 *
	 * @return array<string, list<class-string<IGovernanceResourceProvider>>> keyed by app ID
	 * @experimental 36.0.0
	 */
	public function getProviders(): array;
}
