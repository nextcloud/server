/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'
import type { ShareSetting } from '../files_sharing/FilesSharingUtils.ts'

import { basename } from '@nextcloud/paths'
import { openActionsMenu, triggerActionForFile } from '../files/FilesUtils.ts'
import { createShare } from '../files_sharing/FilesSharingUtils.ts'

export function uploadThreeVersions(user: User, fileName: string) {
	// A version is identified by the file's modification time at second
	// resolution (files_versions/<path>.v<mtime>), so two uploads that resolve to
	// the same second collapse into a single version. Wall-clock spacing (cy.wait)
	// is racy on slow runners: the mtime is set server-side at write time, so
	// jitter can still floor two uploads into the same second. Pin an explicit,
	// distinct mtime per upload instead (via X-OC-MTime) so exactly three versions
	// are always created regardless of runner speed.
	const baseMtime = Math.floor(Date.now() / 1000) - 5
	cy.uploadContent(user, new Blob(['v1'], { type: 'text/plain' }), 'text/plain', `/${fileName}`, baseMtime)
	cy.uploadContent(user, new Blob(['v2'], { type: 'text/plain' }), 'text/plain', `/${fileName}`, baseMtime + 2)
	cy.uploadContent(user, new Blob(['v3'], { type: 'text/plain' }), 'text/plain', `/${fileName}`, baseMtime + 4)
	cy.login(user)
}

export function openVersionsPanel(fileName: string) {
	// Detect the versions list fetch
	cy.intercept('PROPFIND', '**/dav/versions/*/versions/**').as('getVersions')

	triggerActionForFile(basename(fileName), 'details')
	cy.get('[data-cy-sidebar]')
		.as('sidebar')
		.should('be.visible')
	cy.get('@sidebar')
		.find('[aria-controls="tab-files_versions"]')
		.click()

	// Wait for the versions list to be fetched
	cy.wait('@getVersions')
	cy.get('#tab-files_versions').should('be.visible', { timeout: 10000 })
}

function getVersionMenuToggle(index: number) {
	return cy.get('#tab-files_versions [data-files-versions-version]')
		.eq(index)
		.find('button')
}

/**
 * Open a version's actions menu. The version rows use the same NcActions menu
 * as the file list, which on slow (CI) runners can drop the opening click or
 * take several frames to display the popover — so open it with the shared
 * robust helper instead of a single naive click.
 *
 * @param index the version row index
 */
export function openVersionMenu(index: number) {
	openActionsMenu(() => getVersionMenuToggle(index))
}

/**
 * Close a version's actions menu that was opened with openVersionMenu. Clicking
 * the toggle while it is expanded collapses the popover.
 *
 * @param index the version row index
 */
export function closeVersionMenu(index: number) {
	getVersionMenuToggle(index).then(($toggle) => {
		if ($toggle.attr('aria-expanded') === 'true') {
			cy.wrap($toggle).click({ force: true })
		}
	})
}

export function triggerVersionAction(index: number, actionName: string) {
	openVersionMenu(index)
	cy.get(`[data-cy-files-versions-version-action="${actionName}"]`).filter(':visible').click()
}

export function nameVersion(index: number, name: string) {
	cy.intercept('PROPPATCH', '**/dav/versions/*/versions/**').as('labelVersion')
	triggerVersionAction(index, 'label')
	cy.get(':focused').type(`${name}{enter}`)
	cy.wait('@labelVersion')
}

export function restoreVersion(index: number) {
	cy.intercept('MOVE', '**/dav/versions/*/versions/**').as('restoreVersion')
	triggerVersionAction(index, 'restore')
	cy.wait('@restoreVersion')
}

export function deleteVersion(index: number) {
	cy.intercept('DELETE', '**/dav/versions/*/versions/**').as('deleteVersion')
	triggerVersionAction(index, 'delete')
	cy.wait('@deleteVersion')
}

export function doesNotHaveAction(index: number, actionName: string) {
	openVersionMenu(index)
	cy.get(`[data-cy-files-versions-version-action="${actionName}"]`).should('not.exist')
	// Close the menu again so its entries do not leak into the next assertion
	// (the action query above is global).
	closeVersionMenu(index)
}

export function assertVersionContent(index: number, expectedContent: string) {
	cy.intercept({ method: 'GET', times: 1, url: 'remote.php/**' }).as('downloadVersion')
	triggerVersionAction(index, 'download')
	cy.wait('@downloadVersion')
		.then(({ response }) => expect(response?.body).to.equal(expectedContent))
}

export function setupTestSharedFileFromUser(owner: User, randomFileName: string, shareOptions: Partial<ShareSetting>) {
	return cy.createRandomUser()
		.then((recipient) => {
			cy.login(owner)
			cy.visit('/apps/files')
			createShare(randomFileName, recipient.userId, shareOptions)

			cy.login(recipient)
			cy.visit('/apps/files')
			// On a slow backend the freshly created share can be missing from the
			// recipient's first directory listing: the mount cache is updated a
			// moment after the share is committed, and the file list does not
			// refetch on its own. Reload until the shared file shows up so
			// downstream steps start from a stable state.
			reloadUntilFileVisible(basename(randomFileName))
			return cy.wrap(recipient)
		})
}

/**
 * Reload the current file list until the given file appears in it.
 *
 * @param fileName Name of the file expected in the current directory
 * @param attemptsLeft Remaining reloads before giving up
 */
function reloadUntilFileVisible(fileName: string, attemptsLeft = 5) {
	// The list has rendered once at least one row is present (a new user always
	// has welcome.txt), so we can reliably tell "file missing" from "still loading".
	cy.get('[data-cy-files-list-row-name]').should('have.length.at.least', 1)
	cy.get('body').then(($body) => {
		if ($body.find(`[data-cy-files-list-row-name="${CSS.escape(fileName)}"]`).length > 0) {
			return
		}
		if (attemptsLeft === 0) {
			throw new Error(`Shared file "${fileName}" never appeared in the recipient's file list after reloading`)
		}
		cy.reload()
		reloadUntilFileVisible(fileName, attemptsLeft - 1)
	})
}
