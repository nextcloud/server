/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import axios from 'axios'
import { getInlineActionEntryForFile, triggerInlineActionForFile } from '../files/FilesUtils.ts'

/**
 * Add a comment to a file via the DAV API.
 *
 * @param user The user adding the comment
 * @param fileId The numeric file ID
 * @param message The comment text
 */
function addCommentViaApi(user: User, fileId: number, message: string) {
	cy.clearCookies().then(async () => {
		await axios({
			url: `${Cypress.env('baseUrl')}/remote.php/dav/comments/files/${fileId}`,
			method: 'POST',
			auth: {
				username: user.userId,
				password: user.password,
			},
			headers: { 'Content-Type': 'application/json' },
			data: JSON.stringify({ actorType: 'users', verb: 'comment', message }),
		})
	})
}

describe('Comments: unread badge in the files list', { testIsolation: true }, () => {
	let fileOwner: User
	let fileId: number

	beforeEach(() => {
		// The admin user can comment on any file without needing a share
		const admin = { userId: 'admin', password: 'admin' } as User

		cy.createRandomUser().then(($user) => {
			fileOwner = $user
			cy.uploadContent(fileOwner, new Blob(['hello']), 'text/plain', '/commented-file.txt')
				.then((response) => {
					fileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')
					// Add a comment as admin so fileOwner has an unread comment
					addCommentViaApi(admin, fileId, 'Hey, take a look at this!')
				})
		})
	})

	it('shows the unread comments badge when there are unread comments', () => {
		cy.login(fileOwner)
		cy.visit('/apps/files')

		getInlineActionEntryForFile('commented-file.txt', 'comments-unread')
			.should('be.visible')
			.find('button')
			.should('have.attr', 'aria-label')
			.and('match', /new comment/)
	})

	it('removes the unread badge after opening the sidebar comments tab', () => {
		cy.login(fileOwner)
		cy.visit('/apps/files')

		// Verify badge is present first
		getInlineActionEntryForFile('commented-file.txt', 'comments-unread')
			.should('be.visible')

		// Intercept the PROPPATCH that marks comments as read
		cy.intercept('PROPPATCH', /\/remote\.php\/dav\/comments\/files\//).as('markRead')

		// Click the badge — opens the comments or activity sidebar
		triggerInlineActionForFile('commented-file.txt', 'comments-unread')

		cy.get('[data-cy-sidebar]').should('be.visible')

		// The read-marker PROPPATCH must have been sent
		cy.wait('@markRead')

		// Badge must be gone without a page reload
		getInlineActionEntryForFile('commented-file.txt', 'comments-unread')
			.should('not.exist')
	})

	it('badge stays absent after closing and re-opening the sidebar', () => {
		cy.login(fileOwner)
		cy.visit('/apps/files')

		cy.intercept('PROPPATCH', /\/remote\.php\/dav\/comments\/files\//).as('markRead')

		triggerInlineActionForFile('commented-file.txt', 'comments-unread')
		cy.get('[data-cy-sidebar]').should('be.visible')
		cy.wait('@markRead')

		// Close the sidebar
		cy.get('[data-cy-sidebar] .app-sidebar__close').click({ force: true })
		cy.get('[data-cy-sidebar]').should('not.be.visible')

		// Badge must still be gone
		getInlineActionEntryForFile('commented-file.txt', 'comments-unread')
			.should('not.exist')
	})
})
