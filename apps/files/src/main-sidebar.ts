/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { exposeSidebarMount, initializeSidebar } from './sidebar/setup.ts'

import 'vite/modulepreload-polyfill'

// apps can render the sidebar within their own layout at any time
exposeSidebarMount()

// the files app registers its data provider while loading, so wait for all scripts to be executed
if (document.readyState === 'loading') {
	window.addEventListener('DOMContentLoaded', initializeSidebar)
} else {
	initializeSidebar()
}
