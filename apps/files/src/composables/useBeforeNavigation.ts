/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { NavigationGuard } from 'vue-router'

import { onUnmounted } from 'vue'
import { useRouter } from 'vue-router/composables'

/**
 * Helper until we use Vue-Router v4 (Vue3).
 *
 * @param fn - The navigation guard
 */
export function onBeforeNavigation(fn: NavigationGuard) {
	const router = useRouter()
	const remove = router.beforeResolve(fn)
	onUnmounted(remove)
}
