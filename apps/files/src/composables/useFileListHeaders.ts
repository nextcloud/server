/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Header } from '@nextcloud/files'
import type { ComputedRef } from 'vue'

import { getFileListHeaders } from '@nextcloud/files'
import { computed, ref } from 'vue'

/**
 * Get the registered and sorted file list headers.
 */
export function useFileListHeaders(): ComputedRef<Header[]> {
	const headers = ref(getFileListHeaders())
	const sorted = computed(() => [...headers.value].sort((a, b) => a.order - b.order) as Header[])

	return sorted
}
