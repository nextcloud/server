/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FilesFilter } from '../types'

import { inject } from 'vue'

/**
 * Provide functions for adding and deleting filters injected by the files list
 */
export default function() {
	const addFilter = inject<(filter: FilesFilter) => void>('files:filter:add', () => {})
	const deleteFilter = inject<(id: string) => void>('files:filter:delete', () => {})

	return {
		addFilter,
		deleteFilter,
	}
}
