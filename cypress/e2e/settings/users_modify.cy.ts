/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { User } from '@nextcloud/cypress'
import { clearState, getUserListRow, handlePasswordConfirmation, toggleEditButton, waitLoading } from './usersUtils'

const admin = new User('admin', 'admin')

describe('Settings: Change user properties', function() {
	let user: User

	beforeEach(function() {
		clearState()
		cy.createRandomUser().then(($user) => { user = $user })
		cy.login(admin)
	})

	it('Can change the display name', function() {
		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).within(() => {
			// set the display name
			cy.get('[data-cy-user-list-input-displayname]').should('exist').and('have.value', user.userId)
			cy.get('[data-cy-user-list-input-displayname]').clear()
			cy.get('[data-cy-user-list-input-displayname]').type('John Doe')
			cy.get('[data-cy-user-list-input-displayname]').should('have.value', 'John Doe')
			cy.get('[data-cy-user-list-input-displayname] ~ button').click()

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the display name cell is done loading
			waitLoading('[data-cy-user-list-input-displayname]')
		})

		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Display.+name.+was.+successfully.+changed/i).should('exist')
	})

	it('Can change the password', function() {
		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).within(() => {
			// see that the password of user is ""
			cy.get('[data-cy-user-list-input-password]').should('exist').and('have.value', '')
			// set the password for user to 123456
			cy.get('[data-cy-user-list-input-password]').type('123456')
			// When I set the password for user to 123456
			cy.get('[data-cy-user-list-input-password]').should('have.value', '123456')
			cy.get('[data-cy-user-list-input-password] ~ button').click()

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the password cell for user is done loading
			waitLoading('[data-cy-user-list-input-password]')
			// password input is emptied on change
			cy.get('[data-cy-user-list-input-password]').should('have.value', '')
		})

		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Password.+successfully.+changed/i).should('exist')
	})

	it('Can change the email address', function() {
		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).find('[data-cy-user-list-cell-email]').within(() => {
			// see that the email of user is ""
			cy.get('input').should('exist').and('have.value', '')
			// set the email for user to mymail@example.com
			cy.get('input').type('mymail@example.com')
			// When I set the password for user to mymail@example.com
			cy.get('input').should('have.value', 'mymail@example.com')
			cy.get('input ~ button').click()

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the password cell for user is done loading
			waitLoading('[data-cy-user-list-input-email]')
		})

		// Success message is shown
		cy.get('.toastify.toast-success').contains(/Email.+successfully.+changed/i).should('exist')
	})

	it('Can change the user quota to a predefined one', function() {
		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).find('[data-cy-user-list-cell-quota]').scrollIntoView()
		getUserListRow(user.userId).find('[data-cy-user-list-cell-quota] [data-cy-user-list-input-quota]').within(() => {
			// see that the quota of user is unlimited
			cy.get('.vs__selected').should('exist').and('contain.text', 'Unlimited')
			// Open the quota selector
			cy.get('[role="combobox"]').click({ force: true })
			// see that there are default options for the quota
			cy.get('li').then(($options) => {
				expect($options).to.have.length(5)
				cy.wrap($options).contains('Default quota')
				cy.wrap($options).contains('Unlimited')
				cy.wrap($options).contains('1 GB')
				cy.wrap($options).contains('10 GB')
				// select 5 GB
				cy.wrap($options).contains('5 GB').click({ force: true })

				// Make sure no confirmation modal is shown
				handlePasswordConfirmation(admin.password)
			})
			// see that the quota of user is 5 GB
			cy.get('.vs__selected').should('exist').and('contain.text', '5 GB')
		})

		// see that the changes are loading
		waitLoading('[data-cy-user-list-input-quota]')

		// finish editing the user
		toggleEditButton(user, false)

		// I see that the quota was set on the backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.code).to.equal(0)
			const info = JSON.parse($result.stdout)
			expect(info?.quota).to.equal('5 GB')
		})
	})

	it('Can change the user quota to a custom value', function() {
		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).find('[data-cy-user-list-cell-quota]').scrollIntoView()
		getUserListRow(user.userId).find('[data-cy-user-list-cell-quota]').within(() => {
			// see that the quota of user is unlimited
			cy.get('.vs__selected').should('exist').and('contain.text', 'Unlimited')
			// set the quota to 4 MB
			cy.get('[data-cy-user-list-input-quota] input').type('4 MB{enter}')

			// Make sure no confirmation modal is shown
			handlePasswordConfirmation(admin.password)

			// see that the quota of user is 4 MB
			// TODO: Enable this after the file size handling is fixed
			// cy.get('.vs__selected').should('exist').and('contain.text', '4 MB')

			// see that the changes are loading
			waitLoading('[data-cy-user-list-input-quota]')
		})

		// finish editing the user
		toggleEditButton(user, false)

		// I see that the quota was set on the backend
		cy.runOccCommand(`user:info --output=json '${user.userId}'`).then(($result) => {
			expect($result.code).to.equal(0)
			// TODO: Enable this after the file size handling is fixed!!!!!!
			// const info = JSON.parse($result.stdout)
			// expect(info?.quota).to.equal('4 MB')
		})
	})

	it('Can set manager of a user', function() {
		// create the manager
		let manager: User
		cy.createRandomUser().then(($user) => { manager = $user })

		// open the User settings as admin
		cy.login(admin)
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId)
			.find('[data-cy-user-list-cell-manager]')
			.scrollIntoView()

		getUserListRow(user.userId).find('[data-cy-user-list-cell-manager]').within(() => {
			// see that the user has no manager
			cy.get('.vs__selected').should('not.exist')
			// Open the dropdown menu
			cy.get('[role="combobox"]').click({ force: true })
			// select the manager
			cy.contains('li', manager.userId).click({ force: true })

			// Handle password confirmation on time out
			handlePasswordConfirmation(admin.password)

			// see that the user has a manager set
			cy.get('.vs__selected').should('exist').and('contain.text', manager.userId)
		})

		// see that the changes are loading
		waitLoading('[data-cy-user-list-input-manager]')

		// finish editing the user
		toggleEditButton(user, false)

		// validate the manager is set
		cy.getUserData(user).then(($result) => expect($result.body).to.contain(`<manager>${manager.userId}</manager>`))
	})

	it('Can make user a subadmin of a group', function() {
		// create a group
		const groupName = 'userstestgroup'
		cy.runOccCommand(`group:add '${groupName}'`)

		// open the User settings as admin
		cy.visit('/settings/users')

		// toggle edit button into edit mode
		toggleEditButton(user, true)

		getUserListRow(user.userId).find('[data-cy-user-list-cell-subadmins]').scrollIntoView()
		getUserListRow(user.userId).find('[data-cy-user-list-cell-subadmins]').within(() => {
			// see that the user is no subadmin
			cy.get('.vs__selected').should('not.exist')
			// Open the dropdown menu
			cy.get('[role="combobox"]').click({ force: true })
			// select the group
			cy.contains('li', groupName).click({ force: true })

			// handle password confirmation on time out
			handlePasswordConfirmation(admin.password)

			// see that the user is subadmin of the group
			cy.get('.vs__selected').should('exist').and('contain.text', groupName)
		})

		waitLoading('[data-cy-user-list-input-subadmins]')

		// finish editing the user
		toggleEditButton(user, false)

		// I see that the quota was set on the backend
		cy.getUserData(user).then(($response) => {
			expect($response.status).to.equal(200)
			const dom = (new DOMParser()).parseFromString($response.body, 'text/xml')
			expect(dom.querySelector('subadmin element')?.textContent).to.contain(groupName)
		})
	})
})
