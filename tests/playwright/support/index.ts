/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Public entry point: every Page Object Model and the types they expose.
 *
 * Helpers and fixtures are reached through their own subpaths
 * (`@nextcloud/playwright-poms/utils/dav`, `…/fixtures/files-page`) because
 * every fixture module exports a symbol named `test`.
 */

export * from './sections/AccountMenuPage.ts'
export * from './sections/AdminThemingPage.ts'
export * from './sections/AppstorePage.ts'
export * from './sections/BackgroundFilePickerDialogPage.ts'
export * from './sections/ContactsMenuPage.ts'
export * from './sections/CopyMoveDialogPage.ts'
export * from './sections/DevicesSessionsSettingsPage.ts'
export * from './sections/ExternalStorageSettingsPage.ts'
export * from './sections/FilePickerDialogPage.ts'
export * from './sections/FilesFilterPage.ts'
export * from './sections/FilesListPage.ts'
export * from './sections/FilesNavigationPage.ts'
export * from './sections/FilesSidebarPage.ts'
export * from './sections/LanguageLocaleSettingsPage.ts'
export * from './sections/LoginPage.ts'
export * from './sections/NavigationHeaderPage.ts'
export * from './sections/ProfileContactSettingsPage.ts'
export * from './sections/PublicSharePage.ts'
export * from './sections/SettingsUsersPage.ts'
export * from './sections/SetupPage.ts'
export * from './sections/SharingDialogPage.ts'
export * from './sections/SharingTab.ts'
export * from './sections/SystemTagsFilesListPage.ts'
export * from './sections/TransferOwnershipPage.ts'
export * from './sections/TrashbinListPage.ts'
export * from './sections/UnifiedSearchPage.ts'
export * from './sections/UnifiedShareListPage.ts'
export * from './sections/UserThemingPage.ts'
export * from './sections/VersionsTab.ts'
