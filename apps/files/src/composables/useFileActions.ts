/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ActionContext, IFileAction } from '@nextcloud/files'
import type { MaybeRefOrGetter } from '@vueuse/core'
import type { Ref } from 'vue'

import { getFileActions, getFilesRegistry } from '@nextcloud/files'
import { toValue } from '@vueuse/core'
import { computed, readonly, ref } from 'vue'

const actions = ref<IFileAction[] | undefined>()

/**
 * Get the registered and sorted file actions.
 */
export function useFileActions() {
	if (!actions.value) {
		// if not initialized by other component yet, initialize and subscribe to registry changes
		actions.value = getFileActions()
		getFilesRegistry().addEventListener('register:action', () => {
			actions.value = getFileActions()
		})
	}

	return readonly(actions as Ref<IFileAction[]>)
}

/**
 * Get the enabled file actions for the given context.
 *
 * @param context - The context to check the enabled state of the actions against
 */
export function useEnabledFileActions(context: MaybeRefOrGetter<ActionContext>) {
	const actions = useFileActions()
	return computed(() => actions.value
		.filter((action) => action.enabled === undefined
			|| action.enabled(toValue(context)!))
		.sort((a, b) => (a.order ?? 0) - (b.order ?? 0)))
}
