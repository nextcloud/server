/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getNavigation } from '@nextcloud/files'
import Vue from 'vue'
import FilesList from '../views/FilesList.vue'
import { createRouter } from '../router/router.ts'
import { getPinia } from '../store/index.ts'

/** Handle returned by `renderFilesView()` to tear down the embedded instance. */
export interface RenderedFilesView {
	/** Unmount the file list and free the DOM node it was rendered into. */
	destroy: () => void
}

/**
 * Render only the Files file list (breadcrumbs, toolbar, file list) into a
 * foreign DOM element, activating the given Files navigation view.
 *
 * This never renders `NcContent` or `NcAppContent` - those belong to the
 * Files app's own page chrome (see `FilesApp.vue`) and are skipped here so
 * the file list can be embedded as a widget inside another app's page.
 *
 * The host page must dispatch `OCA\Files\Event\LoadFilesApp` server-side
 * beforehand so the Files app's scripts and initial state are loaded, and
 * the target view must already be registered via `getNavigation().register()`.
 *
 * Exposed as `OCP.Files.renderFilesApp()`.
 *
 * @param el the element to mount the file list into
 * @param viewId the id of the Files navigation view to display
 */
export function renderFilesView(el: HTMLElement, viewId: string): RenderedFilesView {
	const router = createRouter('abstract')
	const FilesListVue = Vue.extend(FilesList)
	const vm = new FilesListVue({
		router,
		pinia: getPinia(),
		propsData: {
			embedded: true,
		},
	}).$mount(el)

	// Normally done by the navigation sidebar's route watcher; there is none
	// here, so keep the active view in sync ourselves.
	getNavigation().setActive(viewId)
	router.push({ name: 'filelist', params: { view: viewId } }).catch(() => {})

	return {
		destroy: () => {
			vm.$destroy()
			vm.$el.remove()
		},
	}
}
