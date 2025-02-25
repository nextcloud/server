/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'

import { createShare, openSharingPanel } from './FilesSharingUtils.ts'

describe('files_sharing: Group deleted', { testIsolation: true }, () => {
	let user: User
	let file: string
	let group1: string
	let group2: string

	before(() => {
		cy.createRandomUser().then((randomUser) => {
			user = randomUser

			file = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10) + '.txt'
			cy.uploadContent(user, new Blob(['share to user'], { type: 'text/plain' }), 'text/plain', `/${file}`)

			group1 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
			group2 = Math.random().toString(36).replace(/[^a-z]+/g, '').substring(0, 10)
			cy.runOccCommand(`group:add ${group1}`)
			cy.runOccCommand(`group:add ${group2}`)

			cy.login(user)
			cy.visit('/apps/files')
			createShare(file, group1)
			createShare(file, group2)
		})
	})

	it('deleted groups are removed from shares', () => {
		cy.login(user)
		cy.visit('/apps/files')

		openSharingPanel(file)
		cy.get('[data-cy-files-sharing-internal-list]').within(() => {
			// see that file is shared with group1
			cy.contains('li', `${group1} (group)`)
				.should('exist')
			// see that file is also shared with group2
			cy.contains('li', `${group2} (group)`)
				.should('exist')
		})

		// delete group1
		cy.runOccCommand(`group:delete ${group1}`)

		// reload the page
		cy.reload()

		openSharingPanel(file)
		cy.get('[data-cy-files-sharing-internal-list]').within(() => {
			// see that file is no longer shared with the deleted group1
			cy.contains('li', `${group1} (group)`)
				.should('not.exist')
			// see that file is still shared with group2
			cy.contains('li', `${group2} (group)`)
				.should('exist')
		})
	})
})
