/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Core is moved to the Vue 3 build (build/frontend) in steps. Until it is
// complete, these are the parts built, linted and tested there; the rest of
// core stays with build/frontend-legacy.

/** Components and views, relative to the repository root. */
export const coreVue3Sources = [
	'core/src/components/AccountMenu/**',
	'core/src/components/AppActionIcon.vue',
	'core/src/components/AppIcon.vue',
	'core/src/components/AppMenu*.vue',
	'core/src/components/ContactsMenu/**',
	'core/src/components/LegacyDialogPrompt.vue',
	'core/src/components/login/**',
	'core/src/components/LoginFlow/**',
	'core/src/components/setup/**',
	'core/src/views/AccountMenu.vue',
	'core/src/views/ContactsMenu.vue',
	'core/src/views/Login.vue',
	'core/src/views/LoginFlow*.vue',
	'core/src/views/PublicShareAuth.vue',
	'core/src/views/UnsupportedBrowser.vue',
	'core/src/views/UpdaterAdmin*.vue',
	'core/src/views/WebInstaller.vue',
]

/** Unit tests, relative to the repository root. */
export const coreVue3Specs = [
	'core/src/OC/**/*.spec.ts',
	'core/src/OCP/**/*.spec.ts',
	'core/src/tests/OC/**/*.spec.ts',
	'core/src/tests/components/AccountMenuProfileEntry.spec.ts',
	'core/src/tests/components/AppActionIcon.spec.ts',
	'core/src/tests/components/AppMenu.spec.ts',
	'core/src/tests/components/AppMenuItem.spec.ts',
	'core/src/tests/components/ContactsMenu/**/*.spec.ts',
	'core/src/tests/components/Login/**/*.spec.ts',
	'core/src/tests/utils/**/*.spec.ts',
	'core/src/tests/views/AccountMenu.spec.ts',
	'core/src/tests/views/ContactsMenu.spec.ts',
	'core/src/views/WebInstaller.spec.ts',
]
