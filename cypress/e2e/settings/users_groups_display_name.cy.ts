/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Regression test for: https://github.com/nextcloud/server/issues/55785
 *
 * Tests that group display names are shown correctly in the user editor,
 * even when the group ID differs from the display name (e.g., when long
 * group names get hashed to create the group ID).
 */

import { User } from '@nextcloud/cypress'
import randomString from 'crypto-random-string'
import { getUserListRow, handlePasswordConfirmation, toggleEditButton } from './usersUtils.ts'

const admin = new User('admin', 'admin')

describe('Settings: Group names persist after reload (issue #55785)', () => {
	let testUser: User
	// Use a very long name to ensure Nextcloud hashes it to create the group ID.
	// This creates a test case where group ID !== group display name.
	const randomPart = randomString(80)
	const groupName = `Test Group with Very Long Name ${randomPart}`

	before(() => {
		cy.createRandomUser().then((user) => {
			testUser = user
		})
		cy.runOccCommand(`group:add '${groupName}'`).then(() => {
			// Verify that the group ID is different from the display name
			// (this confirms our test case is valid)
			cy.runOccCommand('group:list --output=json').then((result) => {
				const groups = JSON.parse(result.stdout)
				const groupEntry = Object.entries(groups).find(([, displayName]) => (displayName as string).includes(randomPart),
				)
				if (groupEntry) {
					const [groupId, displayName] = groupEntry
					cy.log(`Group ID: ${groupId}`)
					cy.log(`Display name: ${displayName}`)
					// Assert that ID and name are different (this is what triggers the bug)
					expect(groupId).to.not.equal(displayName)
				}
			})
		})
		cy.login(admin)
		cy.intercept('GET', '**/ocs/v2.php/cloud/groups/details?search=&offset=*&limit=*').as('loadGroups')
		cy.visit('/settings/users')
		cy.wait('@loadGroups')
	})

	it('Assign user to group', () => {
		toggleEditButton(testUser)

		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups] input')
			.click({ force: true })

		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups] input')
			.type(randomPart.slice(0, 10))

		cy.contains('li.vs__dropdown-option', groupName)
			.should('exist')
			.click({ force: true })

		handlePasswordConfirmation(admin.password)

		toggleEditButton(testUser, false)
	})

	it('After page reload, selected group still shows correct name', () => {
		// Visit the users page again to simulate a fresh page load
		cy.visit('/settings/users')

		toggleEditButton(testUser)

		// Verify the selected group displays the name, not the hashed ID
		getUserListRow(testUser.userId)
			.find('[data-cy-user-list-input-groups]')
			.should('exist')
			.within(() => {
				cy.get('.vs__selected').invoke('text').then((displayedText) => {
					expect(displayedText.trim()).to.include('Test Group with Very Long Name')
				})
			})

		toggleEditButton(testUser, false)
	})
})
