/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListAction, IFolder, INode, IView } from '@nextcloud/files'
import type { MaybeRefOrGetter } from '@vueuse/core'
import type { ComputedRef } from 'vue'

import { getFileListActions, getFilesRegistry } from '@nextcloud/files'
import { toValue } from '@vueuse/core'
import { computed, ref } from 'vue'

const actions = ref<IFileListAction[]>()
const sorted = computed(() => [...(actions.value ?? [])].sort((a, b) => a.order - b.order))

/**
 * Get the registered and sorted file list actions.
 */
export function useFileListActions(): ComputedRef<IFileListAction[]> {
	if (!actions.value) {
		// if not initialized by other component yet, initialize and subscribe to registry changes
		actions.value = getFileListActions()
		getFilesRegistry().addEventListener('register:listAction', () => {
			actions.value = getFileListActions()
		})
	}

	return sorted
}

/**
 * Get the enabled file list actions for the given folder, contents and view.
 *
 * @param folder - The current folder
 * @param contents - The contents of the current folder
 * @param view - The current view
 */
export function useEnabledFileListActions(
	folder: MaybeRefOrGetter<IFolder | undefined>,
	contents: MaybeRefOrGetter<INode[]>,
	view: MaybeRefOrGetter<IView | undefined>,
) {
	const actions = useFileListActions()
	return computed(() => {
		if (toValue(folder) === undefined || toValue(view) === undefined) {
			return []
		}

		return actions.value.filter((action) => action.enabled === undefined
			|| action.enabled({ folder: toValue(folder)!, contents: toValue(contents), view: toValue(view)! }))
	})
}
