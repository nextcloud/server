/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/cypress'
import { assertNotExistOrNotVisible, getUserListRow, handlePasswordConfirmation, toggleEditButton } from './usersUtils'

// eslint-disable-next-line n/no-extraneous-import
import randomString from 'crypto-random-string'

const admin = new User('admin', 'admin')

describe('Settings: Create groups', () => {
	before(() => {
		cy.login(admin)
		cy.visit('/settings/users')
	})

	it('Can create a group', () => {
		const groupName = randomString(7)
		// open the Create group menu
		cy.get('button[aria-label="Create group"]').click()

		cy.get('li[data-cy-users-settings-new-group-name]').within(() => {
			// see that the group name is ""
			cy.get('input').should('exist').and('have.value', '')
			// set the group name to foo
			cy.get('input').type(groupName)
			// see that the group name is foo
			cy.get('input').should('have.value', groupName)
			// submit the group name
			cy.get('input ~ button').click()
		})

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)

		// see that the created group is in the list
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			// see that the list of groups contains the group foo
			cy.contains(groupName).should('exist')
		})
	})
})

describe('Settings: Assign user to a group', { testIsolation: false }, () => {
	const groupName = randomString(7)
	let testUser: User

	after(() => cy.deleteUser(testUser))
	before(() => {
		cy.createRandomUser().then((user) => {
			testUser = user
		})
		cy.runOccCommand(`group:add '${groupName}'`)
		cy.login(admin)
		cy.visit('/settings/users')
	})

	it('see that the group is in the list', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').contains('li', groupName).should('exist')
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').contains('li', groupName).within(() => {
			cy.get('.counter-bubble__counter')
				.should('not.exist') // is hidden when 0
		})
	})

	it('see that the user is in the list', () => {
		getUserListRow(testUser.userId)
			.contains(testUser.userId)
			.should('exist')
			.scrollIntoView()
	})

	it('switch into user edit mode', () => {
		toggleEditButton(testUser)
		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups]')
			.should('exist')
	})

	it('assign the group', () => {
		// focus inside the input
		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups] input')
			.click({ force: true })
		// enter the group name
		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups] input')
			.type(`${groupName.slice(0, 5)}`) // only type part as otherwise we would create a new one with the same name
		cy.contains('li.vs__dropdown-option', groupName)
			.click({ force: true })

		handlePasswordConfirmation(admin.password)
	})

	it('leave the user edit mode', () => {
		toggleEditButton(testUser, false)
	})

	it('see the group was successfully assigned', () => {
		// see a new memeber
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]')
			.contains('li', groupName)
			.find('.counter-bubble__counter')
			.should('contain', '1')
	})

	it('validate the user was added on backend', () => {
		cy.runOccCommand(`user:info --output=json '${testUser.userId}'`).then((output) => {
			cy.wrap(output.code).should('eq', 0)
			cy.wrap(JSON.parse(output.stdout)?.groups).should('include', groupName)
		})
	})
})

describe('Settings: Delete an empty group', { testIsolation: false }, () => {
	const groupName = randomString(7)

	before(() => {
		cy.runOccCommand(`group:add '${groupName}'`)
		cy.login(admin)
		cy.visit('/settings/users')
	})

	it('see that the group is in the list', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			// see that the list of groups contains the group foo
			cy.contains(groupName).should('exist').scrollIntoView()
			// open the actions menu for the group
			cy.contains('li', groupName).within(() => {
				cy.get('button.action-item__menutoggle').click({ force: true })
			})
		})
	})

	it('can delete the group', () => {
		// The "Remove group" action in the actions menu is shown and clicked
		cy.get('.action-item__popper button').contains('Remove group').should('exist').click({ force: true })
		// And confirmation dialog accepted
		cy.get('.modal-container button').contains('Confirm').click({ force: true })

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)
	})

	it('deleted group is not shown anymore', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			// see that the list of groups does not contain the group
			cy.contains(groupName).should('not.exist')
		})
		// and also not in database
		cy.runOccCommand('group:list --output=json').then(($response) => {
			const groups: string[] = Object.keys(JSON.parse($response.stdout))
			expect(groups).to.not.include(groupName)
		})
	})
})

describe('Settings: Delete a non empty group', () => {
	let testUser: User
	const groupName = randomString(7)

	before(() => {
		cy.runOccCommand(`group:add '${groupName}'`)
		cy.createRandomUser().then(($user) => {
			testUser = $user
			cy.runOccCommand(`group:addUser '${groupName}' '${$user.userId}'`)
		})
		cy.login(admin)
		cy.visit('/settings/users')
	})
	after(() => cy.deleteUser(testUser))

	it('see that the group is in the list', () => {
		// see that the list of groups contains the group
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').contains('li', groupName).should('exist').scrollIntoView()
	})

	it('can delete the group', () => {
		// open the menu
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]')
			.contains('li', groupName)
			.find('button.action-item__menutoggle')
			.click({ force: true })

		// The "Remove group" action in the actions menu is shown and clicked
		cy.get('.action-item__popper button').contains('Remove group').should('exist').click({ force: true })
		// And confirmation dialog accepted
		cy.get('.modal-container button').contains('Confirm').click({ force: true })

		// Make sure no confirmation modal is shown
		handlePasswordConfirmation(admin.password)
	})

	it('deleted group is not shown anymore', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			// see that the list of groups does not contain the group foo
			cy.contains(groupName).should('not.exist')
		})
		// and also not in database
		cy.runOccCommand('group:list --output=json').then(($response) => {
			const groups: string[] = Object.keys(JSON.parse($response.stdout))
			expect(groups).to.not.include(groupName)
		})
	})
})

describe.only('Settings: Sort groups in the UI', () => {
	before(() => {
		// Clear state
		cy.runOccCommand('group:list --output json').then((output) => {
			const groups = Object.keys(JSON.parse(output.stdout)).filter((group) => group !== 'admin')
			groups.forEach((group) => {
				cy.runOccCommand(`group:delete '${group}'`)
			})
		})

		// Add two groups and add one user to group B
		cy.runOccCommand('group:add A')
		cy.runOccCommand('group:add B')
		cy.createRandomUser().then((user) => {
			cy.runOccCommand(`group:adduser B '${user.userId}'`)
		})

		// Visit the settings as admin
		cy.login(admin)
		cy.visit('/settings/users')
	})

	it('Can set sort by member count', () => {
		// open the settings dialog
		cy.contains('button', 'Account management settings').click()

		cy.contains('.modal-container', 'Account management settings').within(() => {
			cy.get('[data-test="sortGroupsByMemberCount"] input[type="radio"]').scrollIntoView()
			cy.get('[data-test="sortGroupsByMemberCount"] input[type="radio"]').check({ force: true })
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.get('.modal-container').should(el => assertNotExistOrNotVisible(el)))
	})

	it('See that the groups are sorted by the member count', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			cy.get('li').eq(0).should('contain', 'B') // 1 member
			cy.get('li').eq(1).should('contain', 'A') // 0 members
		})
	})

	it('See that the order is preserved after a reload', () => {
		cy.reload()
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			cy.get('li').eq(0).should('contain', 'B') // 1 member
			cy.get('li').eq(1).should('contain', 'A') // 0 members
		})
	})

	it('Can set sort by group name', () => {
		// open the settings dialog
		cy.contains('button', 'Account management settings').click()

		cy.contains('.modal-container', 'Account management settings').within(() => {
			cy.get('[data-test="sortGroupsByName"] input[type="radio"]').scrollIntoView()
			cy.get('[data-test="sortGroupsByName"] input[type="radio"]').check({ force: true })
			// close the settings dialog
			cy.get('button.modal-container__close').click()
		})
		cy.waitUntil(() => cy.get('.modal-container').should(el => assertNotExistOrNotVisible(el)))
	})

	it('See that the groups are sorted by the user count', () => {
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			cy.get('li').eq(0).should('contain', 'A')
			cy.get('li').eq(1).should('contain', 'B')
		})
	})

	it('See that the order is preserved after a reload', () => {
		cy.reload()
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]').within(() => {
			cy.get('li').eq(0).should('contain', 'A')
			cy.get('li').eq(1).should('contain', 'B')
		})
	})
})
