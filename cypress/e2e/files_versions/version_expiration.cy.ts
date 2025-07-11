/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { assertVersionContent, nameVersion, openVersionsPanel, uploadThreeVersions } from './filesVersionsUtils'

describe('Versions expiration', () => {
	let randomFileName = ''

	beforeEach(() => {
		randomFileName = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'

		cy.createRandomUser()
			.then((user) => {
				uploadThreeVersions(user, randomFileName)
				cy.login(user)
				cy.visit('/apps/files')
				openVersionsPanel(randomFileName)
			})
	})

	it('Expire all versions', () => {
		cy.runOccCommand('config:system:set versions_retention_obligation --value \'0, 0\'')
		cy.runOccCommand('versions:expire')
		cy.runOccCommand('config:system:set versions_retention_obligation --value auto')
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 1)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
		})

		assertVersionContent(0, 'v3')
	})

	it('Expire versions v2', () => {
		nameVersion(2, 'v1')

		cy.runOccCommand('config:system:set versions_retention_obligation --value \'0, 0\'')
		cy.runOccCommand('versions:expire')
		cy.runOccCommand('config:system:set versions_retention_obligation --value auto')
		cy.visit('/apps/files')
		openVersionsPanel(randomFileName)

		cy.get('#tab-version_vue').within(() => {
			cy.get('[data-files-versions-version]').should('have.length', 2)
			cy.get('[data-files-versions-version]').eq(0).contains('Current version')
			cy.get('[data-files-versions-version]').eq(1).contains('v1')
		})

		assertVersionContent(0, 'v3')
		assertVersionContent(1, 'v1')
	})
})
