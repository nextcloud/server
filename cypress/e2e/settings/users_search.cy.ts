/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/// <reference types="cypress-if" />

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState } from '../../support/commonUtils.ts'
import { randomString } from '../../support/utils/randomString.ts'
import { getUserList, getUserListRow } from './usersUtils.ts'

const admin = new User('admin', 'admin')

/** Scope role queries to the account management sidebar so they don't match
 * unrelated elements (e.g. the global unified search bar at the top of the page).
 */
function accountNav() {
	return cy.findByRole('navigation', { name: /account management/i })
}

function waitForSearchRequest(alias: string, expectedSearch: string) {
	return cy.wait(alias).then(({ request }) => {
		expect(new URL(request.url).searchParams.get('search')).to.equal(expectedSearch)
	})
}

describe('Settings: Unified search for accounts and groups', { testIsolation: false }, () => {
	// Use a stable, searchable prefix in the group name so we can match
	// it independently from the random user id below.
	const matchingGroup = `zzz-match-${randomString(5)}`
	const otherGroup = `aaa-other-${randomString(5)}`
	let alice: User
	let bob: User

	after(() => {
		cy.deleteUser(alice)
		cy.deleteUser(bob)
		cy.runOccCommand(`group:delete '${matchingGroup}'`, { failOnNonZeroExit: false })
		cy.runOccCommand(`group:delete '${otherGroup}'`, { failOnNonZeroExit: false })
	})

	before(() => {
		clearState()

		cy.createRandomUser().then((user) => {
			alice = user
		})
		cy.createRandomUser().then((user) => {
			bob = user
		})

		cy.runOccCommand(`group:add '${matchingGroup}'`)
		cy.runOccCommand(`group:add '${otherGroup}'`)

		cy.login(admin)
		cy.intercept('GET', '**/ocs/v2.php/cloud/groups/details?search=*').as('initialLoadGroups')
		cy.intercept('GET', '**/ocs/v2.php/cloud/users/details?*').as('initialLoadUsers')
		cy.visit('/settings/users')
		cy.wait('@initialLoadGroups')
		cy.wait('@initialLoadUsers')
	})

	beforeEach(() => {
		// Intercept aliases reset between tests even with testIsolation: false,
		// so re-register them here to capture requests triggered inside each test.
		cy.intercept('GET', '**/ocs/v2.php/cloud/groups/details?search=*').as('loadGroups')
		cy.intercept('GET', '**/ocs/v2.php/cloud/users/details?*').as('loadUsers')
	})

	it('shows the search input in the navigation sidebar', () => {
		accountNav().findByRole('textbox', { name: /search accounts and groups/i })
			.should('be.visible')
			.and('have.value', '')
	})

	it('dispatches the query to both the users and groups API', () => {
		accountNav().findByRole('textbox', { name: /search accounts and groups/i })
			.type(alice.userId)

		// A single keystroke sequence debounces once (300ms), then fans out
		// to both APIs — both requests must carry the same search term.
		cy.wait('@loadUsers').its('request.url').should('include', `search=${alice.userId}`)
		cy.wait('@loadGroups').its('request.url').should('include', `search=${alice.userId}`)

		// The user list reflects what the backend returned for this query.
		getUserListRow(alice.userId).should('exist')
		getUserList().should('not.contain', bob.userId)
	})

	it('filters the group list when the query matches a group name', () => {
		accountNav().findByRole('textbox', { name: /search accounts and groups/i })
			.clear()
			.type(matchingGroup)

		cy.wait('@loadGroups').its('request.url').should('include', `search=${matchingGroup}`)

		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]')
			.should('contain', matchingGroup)
			.and('not.contain', otherGroup)
	})

	it('resets both lists when the clear button is clicked', () => {
		accountNav().findByRole('button', { name: /clear search/i }).click()

		accountNav().findByRole('textbox', { name: /search accounts and groups/i })
			.should('have.value', '')

		waitForSearchRequest('@loadUsers', '')
		waitForSearchRequest('@loadGroups', '')

		getUserListRow(alice.userId).should('exist')
		getUserListRow(bob.userId).should('exist')
		cy.get('ul[data-cy-users-settings-navigation-groups="custom"]')
			.should('contain', matchingGroup)
			.and('contain', otherGroup)
	})
})
