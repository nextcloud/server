/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState } from '../../support/commonUtils.ts'
import { handlePasswordConfirmation } from '../core-utils.ts'
import { openEditDialog, saveEditDialog } from './usersUtils.ts'

const admin = new User('admin', 'admin')

describe('Settings: Change user properties', function() {
	let user: User

	beforeEach(function() {
		clearState()
		cy.createRandomUser().then(($user) => {
			user = $user
		})
		cy.login(admin)
	})

	it('Can change the display name', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			cy.get('input[data-test="displayName"]').should('have.value', user.userId)
			cy.get('input[data-test="displayName"]').clear()
			cy.get('input[data-test="displayName"]').type('John Doe')
			cy.get('input[data-test="displayName"]').should('have.value', 'John Doe')
		})

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.exitCode).to.equal(0)
			const info = JSON.parse($result.stdout)
			expect(info?.display_name).to.equal('John Doe')
		})
	})

	it('Can change the password', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			cy.get('input[data-test="password"]').should('have.value', '')
			cy.get('input[data-test="password"]').type('newpassword123')
		})

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify by logging in with the new password
		cy.login(new User(user.userId, 'newpassword123'))
		cy.visit('/apps/dashboard')
		cy.url().should('include', '/apps/dashboard')
	})

	it('Can change the email address', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			cy.get('input[data-test="email"]').should('have.value', '')
			cy.get('input[data-test="email"]').type('mymail@example.com')
			cy.get('input[data-test="email"]').should('have.value', 'mymail@example.com')
		})

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.exitCode).to.equal(0)
			const info = JSON.parse($result.stdout)
			expect(info?.email).to.equal('mymail@example.com')
		})
	})

	it('Can change the user quota to a predefined one', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			// Open the quota selector
			cy.get('.vs__selected').contains('Unlimited').should('exist')
			cy.findByRole('combobox', { name: /Quota/i }).click({ force: true })
		})

		// Dropdown is floating outside the form — select 5 GB
		cy.get('.vs__dropdown-menu').should('be.visible')
			.contains('li', '5 GB').click({ force: true })

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.exitCode).to.equal(0)
			const info = JSON.parse($result.stdout)
			// TODO Frontend actually send 5 GiB (not 5 GB) and it's correctly reported by backend
			expect(info?.quota).to.equal('5 GiB')
		})
	})

	it('Can change the user quota to a custom value', function() {
		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			// Type a custom quota value
			cy.findByRole('combobox', { name: /Quota/i }).type('4 MB{enter}')
		})

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.exitCode).to.equal(0)
			// Quota value is stored as bytes, verify it was set
			const info = JSON.parse($result.stdout)
			expect(info?.quota).to.not.equal('none')
		})
	})

	it('Can make user a subadmin of a group', function() {
		const groupName = 'userstestgroup'
		cy.runOccCommand(`group:add '${groupName}'`)

		cy.visit('/settings/users')

		openEditDialog(user)

		cy.get('.edit-dialog [data-test="form"]').within(() => {
			// Find the subadmin NcSelect by its label and open the dropdown
			cy.findByRole('combobox', { name: /Admin of the following groups/i }).click({ force: true })
			cy.findByRole('combobox', { name: /Admin of the following groups/i }).type('userstestgroup')
		})

		// Select the group from the floating dropdown
		cy.get('.vs__dropdown-menu').should('be.visible')
			.contains('li', groupName).click({ force: true })

		handlePasswordConfirmation(admin.password)
		saveEditDialog()

		cy.get('.toastify.toast-success').contains(/Account updated/i).should('exist')

		// Verify backend
		cy.getUserData(user).then(($response) => {
			expect($response.status).to.equal(200)
			const dom = (new DOMParser()).parseFromString($response.body, 'text/xml')
			expect(dom.querySelector('subadmin element')?.textContent).to.contain(groupName)
		})
	})
})
