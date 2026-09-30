/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Core is moved to the Vue 3 build (build/frontend) in steps. Until it is
// complete, these are the parts built, linted and tested there; the rest of
// core stays with build/frontend-legacy.

/** Components and views, relative to the repository root. */
export const coreVue3Sources = [
	'core/src/components/login/**',
	'core/src/components/LoginFlow/**',
	'core/src/components/setup/**',
	'core/src/views/Login.vue',
	'core/src/views/LoginFlow*.vue',
	'core/src/views/PublicShareAuth.vue',
	'core/src/views/UnsupportedBrowser.vue',
	'core/src/views/UpdaterAdmin*.vue',
	'core/src/views/WebInstaller.vue',
]

/** Unit tests, relative to the repository root. */
export const coreVue3Specs = [
	'core/src/tests/components/Login/**/*.spec.ts',
	'core/src/views/WebInstaller.spec.ts',
]
