/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NavigationGuard } from 'vue-router'

import { onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

/**
 * Register a global navigation guard for the lifetime of the calling component.
 *
 * For components that are not rendered by a `RouterView` - such as the ones in
 * the app navigation - the in-component guards never run, so they need this.
 *
 * @param fn - The navigation guard
 */
export function onBeforeNavigation(fn: NavigationGuard) {
	const router = useRouter()
	const remove = router.beforeResolve(fn)
	onUnmounted(remove)
}
