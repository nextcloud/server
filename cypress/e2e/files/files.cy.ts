/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
describe('Files', { testIsolation: true }, () => {
	beforeEach(() => {
		cy.createRandomUser().then((user) => {
			cy.login(user)
		})
	})

	it('Login with a user and open the files app', () => {
		cy.visit('/apps/files')
		cy.get('[data-cy-files-list] [data-cy-files-list-row-name="welcome.txt"]').should('be.visible')
	})
})
