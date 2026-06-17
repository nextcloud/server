/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListHeader } from '@nextcloud/files'
import type { ComputedRef } from 'vue'

import { getFileListHeaders, getFilesRegistry } from '@nextcloud/files'
import { computed, ref } from 'vue'

const headers = ref<IFileListHeader[]>()
const sorted = computed(() => [...(headers.value ?? [])].sort((a, b) => a.order - b.order) as IFileListHeader[])

/**
 * Get the registered and sorted file list headers.
 */
export function useFileListHeaders(): ComputedRef<IFileListHeader[]> {
	if (!headers.value) {
		// if not initialized by other component yet, initialize and subscribe to registry changes
		headers.value = getFileListHeaders()
		getFilesRegistry().addEventListener('register:listHeader', () => {
			headers.value = getFileListHeaders()
		})
	}

	return sorted
}
