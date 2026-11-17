/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, IView } from '@nextcloud/files'
import type { App } from 'vue'
import type FilesHeaderPendingShares from '../views/FilesHeaderPendingShares.vue'

import { registerFileListHeader } from '@nextcloud/files'
import { createApp } from 'vue'
import { sharesViewId } from '../files_views/shares.ts'

/**
 * Whether to show pending shares: on top of "All files" and "Shares"
 *
 * @param folder The current folder
 * @param view The current view
 */
export function isPendingSharesEnabled(folder: IFolder, view: IView): boolean {
	return ['files', sharesViewId].includes(view.id) && folder.path === '/'
}

/**
 * Register the pending shares as a files list header
 */
export default function registerPendingShares() {
	let app: App
	let headerComponent: typeof FilesHeaderPendingShares
	let instance: InstanceType<typeof FilesHeaderPendingShares>

	registerFileListHeader({
		id: 'pending-shares',
		order: 1,
		enabled: isPendingSharesEnabled,
		updated: (folder: IFolder, view: IView) => {
			if (instance && isPendingSharesEnabled(folder, view)) {
				instance.update()
			}
		},
		render: async (el: HTMLElement, folder: IFolder, view: IView) => {
			if (app) {
				app.unmount()
			} else {
				headerComponent = (await import('../views/FilesHeaderPendingShares.vue')).default
			}
			app = createApp(headerComponent)
			instance = app.mount(el) as InstanceType<typeof FilesHeaderPendingShares>
			if (isPendingSharesEnabled(folder, view)) {
				instance.update()
			}
		},
	})
}
