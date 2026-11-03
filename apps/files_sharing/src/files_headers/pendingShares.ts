/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, IView } from '@nextcloud/files'
import type { ComponentPublicInstance, VueConstructor } from 'vue'

import { registerFileListHeader } from '@nextcloud/files'
import Vue from 'vue'
import { sharesViewId } from '../files_views/shares.ts'

type IFilesHeaderPendingShares = ComponentPublicInstance & { update: () => Promise<void> }

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
	let FilesHeaderPendingShares: VueConstructor
	let instance: IFilesHeaderPendingShares

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
			if (FilesHeaderPendingShares === undefined) {
				const { default: component } = await import('../views/FilesHeaderPendingShares.vue')
				FilesHeaderPendingShares = Vue.extend(component)
			}
			instance?.$destroy()
			instance = new FilesHeaderPendingShares().$mount(el) as unknown as IFilesHeaderPendingShares
			if (isPendingSharesEnabled(folder, view)) {
				instance.update()
			}
		},
	})
}
