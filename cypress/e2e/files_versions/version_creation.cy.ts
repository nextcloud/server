/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils'

describe('Versions creation', () => {
	let randomFileName = ''

	before(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'

		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Opens the versions panel and sees the versions', () => {
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 3)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version')
		})
	})
})
