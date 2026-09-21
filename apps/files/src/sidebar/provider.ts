/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISidebarDataProvider } from './types.ts'

import { logger } from '../utils/logger.ts'
import { getSidebarSharedState } from './sharedState.ts'

/**
 * Register the data provider backing the sidebar.
 * The app that renders the sidebar owns the provider, so only one can be registered.
 *
 * @param newProvider - The provider to register
 * @throws {Error} If a data provider is already registered
 */
export function setSidebarDataProvider(newProvider: ISidebarDataProvider): void {
	const { dataProvider } = getSidebarSharedState()
	if (dataProvider.value !== undefined) {
		throw new Error('A sidebar data provider is already registered.')
	}

	logger.debug('sidebar: data provider registered')
	dataProvider.value = newProvider
}

/**
 * Get the registered sidebar data provider, if any.
 */
export function getSidebarDataProvider(): ISidebarDataProvider | undefined {
	return getSidebarSharedState().dataProvider.value
}

/**
 * Whether a sidebar data provider is registered.
 */
export function hasSidebarDataProvider(): boolean {
	return getSidebarDataProvider() !== undefined
}

/**
 * Unregister the current data provider.
 *
 * @internal
 */
export function resetSidebarDataProvider(): void {
	getSidebarSharedState().dataProvider.value = undefined
}
