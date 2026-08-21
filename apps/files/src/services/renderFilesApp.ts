/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import FilesApp from '../FilesApp.vue'
import { createRouter } from '../router/router.ts'
import { getPinia } from '../store/index.ts'

/**
 * Render the full Files app UI (navigation, file list, sidebar) into a foreign
 * DOM element, activating the given Files navigation view.
 *
 * The host page must dispatch `OCA\Files\Event\LoadFilesApp` server-side
 * beforehand so the Files app's scripts and initial state are loaded, and
 * the target view must already be registered via `getNavigation().register()`.
 *
 * Exposed as `OCP.Files.renderFilesApp()`.
 *
 * @param el the element to mount the Files app into
 * @param viewId the id of the Files navigation view to display
 */
export function renderFilesApp(el: HTMLElement, viewId: string): void {
	const router = createRouter('abstract')
	const FilesAppVue = Vue.extend(FilesApp)
	new FilesAppVue({
		router,
		pinia: getPinia(),
	}).$mount(el)

	router.push({ name: 'filelist', params: { view: viewId } }).catch(() => {})
}
