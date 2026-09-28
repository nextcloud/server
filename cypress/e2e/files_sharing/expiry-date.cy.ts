/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { closeSidebar } from '../files/FilesUtils.ts'
import { createShare, openSharingDetails, openSharingPanel, updateShare } from './FilesSharingUtils.ts'

describe('files_sharing: Expiry date', () => {
	const expectedDefaultDate = new Date(Date.now() + 2 * 24 * 60 * 60 * 1000)
	const expectedDefaultDateString = `${expectedDefaultDate.getFullYear()}-${String(expectedDefaultDate.getMonth() + 1).padStart(2, '0')}-${String(expectedDefaultDate.getDate()).padStart(2, '0')}`
	const fortnight = new Date(Date.now() + 14 * 24 * 60 * 60 * 1000)
	const fortnightString = `${fortnight.getFullYear()}-${String(fortnight.getMonth() + 1).padStart(2, '0')}-${String(fortnight.getDate()).padStart(2, '0')}`

	let alice: User
	let bob: User

	before(() => {
		// Ensure we have the admin setting setup for default dates with 2 days in the future
		cy.runOccCommand('config:app:set --value yes core shareapi_default_internal_expire_date')
		cy.runOccCommand('config:app:set --value 2 core shareapi_internal_expire_after_n_days')

		cy.createRandomUser().then((user) => {
			alice = user
			cy.login(alice)
		})
		cy.createRandomUser().then((user) => {
			bob = user
		})
	})

	after(() => {
		cy.runOccCommand('config:app:delete core shareapi_default_internal_expire_date')
		cy.runOccCommand('config:app:delete core shareapi_enforce_internal_expire_date')
		cy.runOccCommand('config:app:delete core shareapi_internal_expire_after_n_days')
	})

	beforeEach(() => {
		cy.runOccCommand('config:app:delete core shareapi_enforce_internal_expire_date')
	})

	it('See default expiry date is set and enforced', () => {
		// Enforce the date
		cy.runOccCommand('config:app:set --value yes core shareapi_enforce_internal_expire_date')
		const dir = 'defaultExpiryDateEnforced'
		prepareDirectory(dir)

		validateExpiryDate(dir, expectedDefaultDateString)
		cy.findByRole('checkbox', { name: /expiration date/i })
			.should('be.checked')
			.and('be.disabled')
	})

	it('See default expiry date is set also if not enforced', () => {
		const dir = 'defaultExpiryDate'
		prepareDirectory(dir)

		validateExpiryDate(dir, expectedDefaultDateString)
		cy.findByRole('checkbox', { name: /expiration date/i })
			.should('be.checked')
			.and('not.be.disabled')
			.check({ force: true, scrollBehavior: 'nearest' })
	})

	it('Can set custom expiry date', () => {
		const dir = 'customExpiryDate'
		prepareDirectory(dir)
		updateShare(dir, 0, { expiryDate: fortnight })
		validateExpiryDate(dir, fortnightString)
	})

	it('Custom expiry date survives reload', () => {
		const dir = 'customExpiryDateReload'
		prepareDirectory(dir)
		updateShare(dir, 0, { expiryDate: fortnight })
		validateExpiryDate(dir, fortnightString)

		cy.visit('/apps/files')
		validateExpiryDate(dir, fortnightString)
	})

	/**
	 * Regression test for https://github.com/nextcloud/server/pull/50192
	 * Ensure that admin default settings do not always override the user set value.
	 */
	it('Custom expiry date survives unrelated update', () => {
		const dir = 'customExpiryUnrelatedChanges'
		prepareDirectory(dir)
		updateShare(dir, 0, { expiryDate: fortnight })
		validateExpiryDate(dir, fortnightString)

		closeSidebar()
		updateShare(dir, 0, { note: 'Only note changed' })
		validateExpiryDate(dir, fortnightString)

		cy.visit('/apps/files')
		validateExpiryDate(dir, fortnightString)
	})

	/**
	 * Prepare directory, login and share to bob
	 *
	 * @param name The directory name
	 */
	function prepareDirectory(name: string) {
		cy.mkdir(alice, `/${name}`)
		cy.login(alice)
		cy.visit('/apps/files')
		createShare(name, bob.userId)
	}

	/**
	 * Validate expiry date on a share
	 *
	 * @param filename The filename to validate
	 * @param expectedDate The expected date in YYYY-MM-dd
	 */
	function validateExpiryDate(filename: string, expectedDate: string) {
		openSharingPanel(filename)
		openSharingDetails(0)

		cy.get('#share-date-picker')
			.should('exist')
			.and('have.value', expectedDate)
	}

})
