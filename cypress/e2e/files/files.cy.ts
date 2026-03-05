import type { User } from '@nextcloud/e2e-test-server/cypress'

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
describe('Files', { testIsolation: true }, () => {
	let currentUser: User

	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			currentUser = user
		})
	})

	it('Login with a user and open the files app', () => {
		cy.login(currentUser)
		cy.visit('/apps/files')
		cy.get('[data-cy-files-list] [data-cy-files-list-row-name="welcome.txt"]').should('be.visible')
	})

	it('Opens a valid file shows it as active', () => {
		cy.uploadContent(currentUser, new Blob(), 'text/plain', '/original.txt').then((response) => {
			const fileId = Number.parseInt(response.headers['oc-fileid'] ?? '0')

			cy.login(currentUser)
			cy.visit('/apps/files/files/' + fileId)

			cy.get(`[data-cy-files-list-row-fileid=${fileId}]`)
				.should('be.visible')
			cy.get(`[data-cy-files-list-row-fileid=${fileId}]`)
				.invoke('attr', 'data-cy-files-list-row-name').should('eq', 'original.txt')
			cy.get(`[data-cy-files-list-row-fileid=${fileId}]`)
				.invoke('attr', 'class').should('contain', 'active')
			cy.contains('The file could not be found').should('not.exist')
		})
	})

	it('Opens a valid folder shows its content', () => {
		cy.mkdir(currentUser, '/folder').then(() => {
			cy.login(currentUser)
			cy.visit('/apps/files/files?dir=/folder')

			cy.get('[data-cy-files-content-breadcrumbs]').contains('folder').should('be.visible')
			cy.contains('The file could not be found').should('not.exist')
		})
	})

	it('Opens an unknown file show an error', () => {
		cy.intercept('PROPFIND', /\/remote.php\/dav\//).as('propfind')
		cy.login(currentUser)
		cy.visit('/apps/files/files/123456')

		cy.wait('@propfind')
		// The toast should be visible
		cy.contains('The file could not be found', { timeout: 5000 }).should('be.visible')
	})
})
