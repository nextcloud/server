/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/cypress'
import { handlePasswordConfirmation } from './usersUtils.ts'

let user: User

enum Visibility {
	Private = 'Private',
	Local = 'Local',
	Federated = 'Federated',
	Public = 'Published'
}

const ALL_VISIBILITIES = [Visibility.Public, Visibility.Private, Visibility.Local, Visibility.Federated]

/**
 * Get the input connected to a specific label
 * @param label The content of the label
 */
const inputForLabel = (label: string) => cy.contains('label', label).then((el) => cy.get(`#${el.attr('for')}`))

/**
 * Get the property visibility button
 * @param property The property to which to look for the button
 */
const getVisibilityButton = (property: string) => cy.get(`button[aria-label*="Change scope level of ${property.toLowerCase()}"`)

/**
 * Validate a specifiy visibility is set for a property
 * @param property The property
 * @param active The active visibility
 */
const validateActiveVisibility = (property: string, active: Visibility) => {
	getVisibilityButton(property)
		.should('have.attr', 'aria-label')
		.and('match', new RegExp(`current scope is ${active}`, 'i'))
	getVisibilityButton(property)
		.click()
	cy.get('ul[role="menu"]')
		.contains('button', active)
		.should('have.attr', 'aria-checked', 'true')

	// close menu
	getVisibilityButton(property)
		.click()
}

/**
 * Set a specific visibility for a property
 * @param property The property
 * @param active The visibility to set
 */
const setActiveVisibility = (property: string, active: Visibility) => {
	getVisibilityButton(property)
		.click()
	cy.get('ul[role="menu"]')
		.contains('button', active)
		.click({ force: true })
	handlePasswordConfirmation(user.password)

	cy.wait('@submitSetting')
}

/**
 * Helper to check that setting all visibilities on a property is possible
 * @param property The property to test
 * @param defaultVisibility The default visibility of that property
 * @param allowedVisibility Visibility that is allowed and need to be checked
 */
const checkSettingsVisibility = (property: string, defaultVisibility: Visibility = Visibility.Local, allowedVisibility: Visibility[] = ALL_VISIBILITIES) => {
	getVisibilityButton(property)
		.scrollIntoView()

	validateActiveVisibility(property, defaultVisibility)

	allowedVisibility.forEach((active) => {
		setActiveVisibility(property, active)

		cy.reload()
		getVisibilityButton(property).scrollIntoView()

		validateActiveVisibility(property, active)
	})

	// TODO: Fix this in vue library then enable this test again
	/* // Test that not allowed options are disabled
	ALL_VISIBILITIES.filter((v) => !allowedVisibility.includes(v)).forEach((disabled) => {
		getVisibilityButton(property)
			.click()
		cy.get('ul[role="dialog"')
			.contains('button', disabled)
			.should('exist')
			.and('have.attr', 'disabled', 'true')
	}) */
}

const genericProperties = ['Location', 'X (formerly Twitter)', 'Fediverse']
const nonfederatedProperties = ['Organisation', 'Role', 'Headline', 'About']

describe('Settings: Change personal information', { testIsolation: true }, () => {
	let snapshot: string = ''

	before(() => {
		// ensure we can set locale and language
		cy.runOccCommand('config:system:delete force_language')
		cy.runOccCommand('config:system:delete force_locale')
		cy.createRandomUser().then(($user) => {
			user = $user
			cy.modifyUser(user, 'language', 'en')
			cy.modifyUser(user, 'locale', 'en_US')

			// Make sure the user is logged in at least once
			// before the snapshot is taken to speed up the tests
			cy.login(user)
			cy.visit('/settings/user')

			cy.saveState().then(($snapshot) => {
				snapshot = $snapshot
			})
		})
	})

	after(() => {
		cy.runOccCommand('config:system:set force_language --value en')
		cy.runOccCommand('config:system:set force_locale --value en_US')
	})

	beforeEach(() => {
		cy.login(user)
		cy.visit('/settings/user')
		cy.intercept('PUT', /ocs\/v2.php\/cloud\/users\//).as('submitSetting')
	})

	afterEach(() => {
		cy.restoreState(snapshot)
	})

	it('Can dis- and enable the profile', () => {
		cy.visit(`/u/${user.userId}`)
		cy.contains('h2', user.userId).should('be.visible')

		cy.visit('/settings/user')
		cy.contains('Enable profile').click()
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		cy.visit(`/u/${user.userId}`, { failOnStatusCode: false })
		cy.contains('h2', 'Profile not found').should('be.visible')

		cy.visit('/settings/user')
		cy.contains('Enable profile').click()
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		cy.visit(`/u/${user.userId}`, { failOnStatusCode: false })
		cy.contains('h2', user.userId).should('be.visible')
	})

	it('Can change language', () => {
		cy.intercept('GET', /settings\/user/).as('reload')
		inputForLabel('Language').scrollIntoView()
		inputForLabel('Language').type('Ned')
		cy.contains('li[role="option"]', 'Nederlands')
			.click()
		cy.wait('@reload')

		// expect language changed
		inputForLabel('Taal').scrollIntoView()
		cy.contains('section', 'Help met vertalen')
	})

	it('Can change locale', () => {
		cy.intercept('GET', /settings\/user/).as('reload')
		cy.clock(new Date(2024, 0, 10))

		// Default is US
		cy.contains('section', '01/10/2024')

		inputForLabel('Locale').scrollIntoView()
		inputForLabel('Locale').type('German')
		cy.contains('li[role="option"]', 'German (Germany')
			.click()
		cy.wait('@reload')

		// expect locale changed
		inputForLabel('Locale').scrollIntoView()
		cy.contains('section', '10.01.2024')
	})

	it('Can set primary email and change its visibility', () => {
		cy.contains('label', 'Email').scrollIntoView()
		// Check invalid input
		inputForLabel('Email').type('foo bar')
		inputForLabel('Email').then(($el) => expect(($el.get(0) as HTMLInputElement).checkValidity()).to.be.false)
		// handle valid input
		inputForLabel('Email').type('{selectAll}hello@example.com')
		handlePasswordConfirmation(user.password)

		cy.wait('@submitSetting')
		cy.reload()
		inputForLabel('Email').should('have.value', 'hello@example.com')

		checkSettingsVisibility(
			'Email',
			Visibility.Federated,
			// It is not possible to set it as private
			ALL_VISIBILITIES.filter((v) => v !== Visibility.Private),
		)

		// check it is visible on the profile
		cy.visit(`/u/${user.userId}`)
		cy.contains('a', 'hello@example.com').should('be.visible').and('have.attr', 'href', 'mailto:hello@example.com')
	})

	it('Can delete primary email', () => {
		cy.contains('label', 'Email').scrollIntoView()
		inputForLabel('Email').type('{selectAll}hello@example.com')
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		// check after reload
		cy.reload()
		inputForLabel('Email').should('have.value', 'hello@example.com')

		// delete email
		cy.get('button[aria-label="Remove primary email"]').click({ force: true })
		cy.wait('@submitSetting')

		// check after reload
		cy.reload()
		inputForLabel('Email').should('have.value', '')
	})

	it('Can set and delete additional emails', () => {
		cy.get('button[aria-label="Add additional email"]').should('be.disabled')
		// we need a primary email first
		cy.contains('label', 'Email').scrollIntoView()
		inputForLabel('Email').type('{selectAll}primary@example.com')
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		// add new email
		cy.get('button[aria-label="Add additional email"]')
			.click()

		// without any value we should not be able to add a second additional
		cy.get('button[aria-label="Add additional email"]').should('be.disabled')

		// fill the first additional
		inputForLabel('Additional email address 1')
			.type('1@example.com')
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		// add second additional email
		cy.get('button[aria-label="Add additional email"]')
			.click()

		// fill the second additional
		inputForLabel('Additional email address 2')
			.type('2@example.com')
		handlePasswordConfirmation(user.password)
		cy.wait('@submitSetting')

		// check the content is saved
		cy.reload()
		inputForLabel('Additional email address 1')
			.should('have.value', '1@example.com')
		inputForLabel('Additional email address 2')
			.should('have.value', '2@example.com')

		// delete the first
		cy.get('button[aria-label="Options for additional email address 1"]')
			.click({ force: true })
		cy.contains('button[role="menuitem"]', 'Delete email')
			.click({ force: true })
		handlePasswordConfirmation(user.password)

		cy.reload()
		inputForLabel('Additional email address 1')
			.should('have.value', '2@example.com')
	})

	it('Can set Full name and change its visibility', () => {
		cy.contains('label', 'Full name').scrollIntoView()
		// handle valid input
		inputForLabel('Full name').type('{selectAll}Jane Doe')
		handlePasswordConfirmation(user.password)

		cy.wait('@submitSetting')
		cy.reload()
		inputForLabel('Full name').should('have.value', 'Jane Doe')

		checkSettingsVisibility(
			'Full name',
			Visibility.Federated,
			// It is not possible to set it as private
			ALL_VISIBILITIES.filter((v) => v !== Visibility.Private),
		)

		// check it is visible on the profile
		cy.visit(`/u/${user.userId}`)
		cy.contains('h2', 'Jane Doe').should('be.visible')
	})

	it('Can set Phone number and its visibility', () => {
		cy.contains('label', 'Phone number').scrollIntoView()
		// Check invalid input
		inputForLabel('Phone number').type('foo bar')
		inputForLabel('Phone number').should('have.attr', 'class').and('contain', '--error')
		// handle valid input
		inputForLabel('Phone number').type('{selectAll}+49 89 721010 99701')
		inputForLabel('Phone number').should('have.attr', 'class').and('not.contain', '--error')
		handlePasswordConfirmation(user.password)

		cy.wait('@submitSetting')
		cy.reload()
		inputForLabel('Phone number').should('have.value', '+498972101099701')

		checkSettingsVisibility('Phone number')

		// check it is visible on the profile
		cy.visit(`/u/${user.userId}`)
		cy.get('a[href="tel:+498972101099701"]').should('be.visible')
	})

	it('Can set Website and change its visibility', () => {
		cy.contains('label', 'Website').scrollIntoView()
		// Check invalid input
		inputForLabel('Website').type('foo bar')
		inputForLabel('Website').then(($el) => expect(($el.get(0) as HTMLInputElement).checkValidity()).to.be.false)
		// handle valid input
		inputForLabel('Website').type('{selectAll}http://example.com')
		handlePasswordConfirmation(user.password)

		cy.wait('@submitSetting')
		cy.reload()
		inputForLabel('Website').should('have.value', 'http://example.com')

		checkSettingsVisibility('Website')

		// check it is visible on the profile
		cy.visit(`/u/${user.userId}`)
		cy.contains('http://example.com').should('be.visible')
	})

	// Check generic properties that allow any visibility and any value
	genericProperties.forEach((property) => {
		it(`Can set ${property} and change its visibility`, () => {
			const uniqueValue = `${property.toUpperCase()} ${property.toLowerCase()}`
			cy.contains('label', property).scrollIntoView()
			inputForLabel(property).type(uniqueValue)
			handlePasswordConfirmation(user.password)

			cy.wait('@submitSetting')
			cy.reload()
			inputForLabel(property).should('have.value', uniqueValue)

			checkSettingsVisibility(property)

			// check it is visible on the profile
			cy.visit(`/u/${user.userId}`)
			cy.contains(uniqueValue).should('be.visible')
		})
	})

	// Check non federated properties - those where we need special configuration and only support local visibility
	nonfederatedProperties.forEach((property) => {
		it(`Can set ${property} and change its visibility`, () => {
			const uniqueValue = `${property.toUpperCase()} ${property.toLowerCase()}`
			cy.contains('label', property).scrollIntoView()
			inputForLabel(property).type(uniqueValue)
			handlePasswordConfirmation(user.password)

			cy.wait('@submitSetting')
			cy.reload()
			inputForLabel(property).should('have.value', uniqueValue)

			checkSettingsVisibility(property, Visibility.Local, [Visibility.Private, Visibility.Local])

			// check it is visible on the profile
			cy.visit(`/u/${user.userId}`)
			cy.contains(uniqueValue).should('be.visible')
		})
	})
})
