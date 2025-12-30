/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { randomString } from '../../support/utils/randomString.ts'
import { navigateToFolder } from '../files/FilesUtils.ts'
import { doesNotHaveAction, nameVersion, openVersionsPanel, setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('Versions naming', () => {
	let randomFileName = ''
	let user: User

	beforeEach(() => {
		randomFileName = randomString(10) + '.txt'

		cy.createRandomUser()
			.then((_user) => {
				user = _user
				cy.mkdir(_user, '/share')
				uploadThreeVersions(user, `share/${randomFileName}`)
			})
	})

	it('Names the versions', () => {
		cy.login(user)
		cy.visit('/apps/files')
		navigateToFolder('share')
		openVersionsPanel(randomFileName)

		nameVersion(2, 'v1')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(2).contains('v1')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})

		nameVersion(1, 'v2')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(1).contains('v2')
		})

		nameVersion(0, 'v3')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(0).contains('v3 (Current version)')
		})
	})

	it('Name versions of shared file with edit permission', () => {
		setupTestSharedFileFromUser(user, 'share', { update: true })

		navigateToFolder('share')
		openVersionsPanel(randomFileName)

		nameVersion(2, 'v1 - shared')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(2).contains('v1 - shared')
			cy.get('[data-files-versions-version]').eq(2).contains('Initial version').should('not.exist')
		})

		nameVersion(1, 'v2 - shared')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(1).contains('v2 - shared')
		})

		nameVersion(0, 'v3 - shared')
		cy.get('#tab-files_versions').within(() => {
			cy.get('[data-files-versions-version]').eq(0).contains('v3 - shared (Current version)')
		})
	})

	it('Name versions without edit permission fails', () => {
		setupTestSharedFileFromUser(user, 'share', { update: false })

		navigateToFolder('share')
		openVersionsPanel(randomFileName)

		cy.get('[data-files-versions-version]')
			.eq(0)
			.as('firstVersion')
			.find('.action-item__menutoggle')
			.should('not.exist')
		cy.get('@firstVersion')
			.find('[data-cy-version-action="label"]')
			.should('not.exist')

		doesNotHaveAction(1, 'label')
		doesNotHaveAction(2, 'label')
	})
})
