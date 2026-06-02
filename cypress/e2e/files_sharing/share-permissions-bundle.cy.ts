/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server/cypress'

import { openSharingPanel } from './FilesSharingUtils.ts'

describe('files_sharing: Share permissions bundle configuration', () => {
	let alice: User
	let bob: User

	before(() => {
		cy.createRandomUser().then(($user) => {
			alice = $user
		})
		cy.createRandomUser().then(($user) => {
			bob = $user
		})
	})

	beforeEach(() => {
		cy.runOccCommand('config:app:delete files_sharing shareapi_exclude_reshare_from_edit')
	})

	after(() => {
		cy.runOccCommand('config:app:delete files_sharing shareapi_exclude_reshare_from_edit')
	})

	/**
	 * Helper to create a user share and select "Allow editing"
	 */
	function createUserShareWithEdit(itemName: string) {
		openSharingPanel(itemName)

		cy.get('#app-sidebar-vue').within(() => {
			cy.intercept('GET', '**/apps/files_sharing/api/v1/sharees?*').as('shareeSearch')
			cy.findByRole('combobox', { name: /Search for internal recipients/i })
				.type(`{selectAll}${bob.userId}`)
			cy.wait('@shareeSearch')
		})

		cy.get(`[user="${bob.userId}"]`).click()

		// Select "Allow editing" permission bundle
		cy.get('[data-cy-files-sharing-share-permissions-bundle]').should('be.visible')
		cy.get('[data-cy-files-sharing-share-permissions-bundle="upload-edit"]').click()

		cy.intercept('POST', '**/ocs/v2.php/apps/files_sharing/api/v1/shares').as('createShare')
		cy.findByRole('button', { name: 'Save share' }).click()

		return cy.wait('@createShare')
	}

	describe('Default behavior (SHARE included in edit)', () => {
		it('Creates user share with "Allow editing" with SHARE permission for folders', () => {
			const folderName = 'test-folder-with-share'
			cy.mkdir(alice, `/${folderName}`)
			cy.login(alice)
			cy.visit('/apps/files')

			createUserShareWithEdit(folderName).should(({ response }) => {
				// Verify permission value is 31 (ALL with SHARE: READ=1 + UPDATE=2 + CREATE=4 + DELETE=8 + SHARE=16)
				expect(response?.body?.ocs?.data?.permissions).to.equal(31)
			})
		})

		it('Creates user share with "Allow editing" with SHARE permission for files', () => {
			const fileName = 'test-file-with-share.txt'
			cy.uploadContent(alice, new Blob(['content']), 'text/plain', `/${fileName}`)
			cy.login(alice)
			cy.visit('/apps/files')

			createUserShareWithEdit(fileName).should(({ response }) => {
				// Verify permission value is 19 (ALL_FILE with SHARE: READ=1 + UPDATE=2 + SHARE=16)
				expect(response?.body?.ocs?.data?.permissions).to.equal(19)
			})
		})
	})

	describe('With SHARE excluded from edit (config enabled)', () => {
		beforeEach(() => {
			cy.runOccCommand('config:app:set --value yes files_sharing shareapi_exclude_reshare_from_edit')
		})

		it('Creates user share with "Allow editing" without SHARE permission for folders', () => {
			const folderName = 'test-folder-no-share'
			cy.mkdir(alice, `/${folderName}`)
			cy.login(alice)
			cy.visit('/apps/files')

			createUserShareWithEdit(folderName).should(({ response }) => {
				// Verify permission value is 15 (ALL without SHARE: READ=1 + UPDATE=2 + CREATE=4 + DELETE=8)
				expect(response?.body?.ocs?.data?.permissions).to.equal(15)
			})
		})

		it('Creates user share with "Allow editing" without SHARE permission for files', () => {
			const fileName = 'test-file-no-share.txt'
			cy.uploadContent(alice, new Blob(['content']), 'text/plain', `/${fileName}`)
			cy.login(alice)
			cy.visit('/apps/files')

			createUserShareWithEdit(fileName).should(({ response }) => {
				// Verify permission value is 3 (ALL_FILE without SHARE: READ=1 + UPDATE=2)
				expect(response?.body?.ocs?.data?.permissions).to.equal(3)
			})
		})
	})
})
