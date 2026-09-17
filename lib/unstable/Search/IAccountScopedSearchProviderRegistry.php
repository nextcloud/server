<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Looks up the {@see IAccountScopedSearchProvider}'s apps declared via
 * {@see IRegistrationContext::registerAccountScopedSearchProvider}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IAccountScopedSearchProviderRegistry {
	/**
	 * Forget any resolved providers, so the next lookup resolves them again.
	 *
	 * @experimental 36.0.0
	 */
	public function clear(): void;

	/**
	 * @return array<non-empty-lowercase-string, IAccountScopedSearchProvider> keyed by getId()
	 * @experimental 36.0.0
	 */
	public function getProviders(): array;

	/**
	 * @experimental 36.0.0
	 */
	public function getProvider(string $id): ?IAccountScopedSearchProvider;
}
