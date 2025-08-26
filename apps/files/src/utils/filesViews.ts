/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { UserConfig } from '../types.ts'

import { loadState } from '@nextcloud/initial-state'

/**
 * Check whether the personal files view can be shown
 */
export function hasPersonalFilesView(): boolean {
	const storageStats = loadState('files', 'storageStats', { quota: -1 })
	// Don't show this view if the user has no storage quota
	return storageStats.quota !== 0
}

/**
 * Get the default files view
 */
export function defaultView() {
	const { default_view: defaultView } = loadState<Partial<UserConfig>>('files', 'config', { default_view: 'files' })

	// the default view - only use the personal one if it is enabled
	if (defaultView !== 'personal' || hasPersonalFilesView()) {
		return defaultView
	}
	return 'files'
}
