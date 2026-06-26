/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Ensure the downloads folder is deleted before each test
 */
export function deleteDownloadsFolderBeforeEach() {
	beforeEach(() => cy.task('deleteFolder', Cypress.config('downloadsFolder')))
}
