/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { NavigationHeader } from '../../pages/NavigationHeader.ts'
import { defaultPrimary, pickColor, validateBodyThemingCss } from './themingUtils.ts'

const admin = new User('admin', 'admin')

describe('User default background settings', function() {
	before(function() {
		cy.resetAdminTheming()
		cy.resetUserTheming(admin)
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Appearance and accessibility settings/ })
			.should('be.visible')
	})

	it('Default is selected on new users', function() {
		cy.findByRole('button', { name: 'Default background', pressed: true })
			.should('exist')
			.scrollIntoView()
	})
})

describe('User select shipped backgrounds and remove background', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Background and color/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Select a shipped background', function() {
		const background = 'anatoly-mikhaltsov-butterfly-wing-scale.jpg'
		const backgroundName = 'Background picture of a red-ish butterfly wing under microscope'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.findByRole('button', { name: backgroundName, pressed: false })
			.click()
		cy.findByRole('button', { name: backgroundName, pressed: true })
			.should('be.visible')

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#a53c17', background, '#652e11'))
	})

	it('Select a bright shipped background', function() {
		const background = 'bernie-cetonia-aurata-take-off-composition.jpg'
		const backgroundName = 'Montage of a cetonia aurata bug that takes off with white background'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		cy.findByRole('button', { name: backgroundName, pressed: false })
			.click()
		cy.findByRole('button', { name: backgroundName, pressed: true })
			.should('be.visible')

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#56633d', background, '#dee0d3'))
	})
})

describe('User select a custom color', function() {
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Background and color/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Select a custom color', function() {
		cy.intercept('*/apps/theming/background/color').as('clearBackground')

		// Clear background
		pickColor(cy.findByRole('button', { name: 'Plain background' }), 7)

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null, '#3794ac'))
	})
})

describe('User select a bright custom color and remove background', function() {
	const navigationHeader = new NavigationHeader()

	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Background and color/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Remove background', function() {
		cy.intercept('*/apps/theming/background/color').as('clearBackground')

		// Clear background
		pickColor(cy.findByRole('button', { name: 'Plain background' }), 4)

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null, '#ddcb55'))
	})

	it('See the header being inverted', function() {
		cy.waitUntil(() => navigationHeader.getNavigationEntries().find('img').then((el) => {
			let ret = true
			el.each(function() {
				ret = ret && window.getComputedStyle(this).filter === 'invert(1)'
			})
			return ret
		}))
	})

	it('Select another but non-bright shipped background', function() {
		const background = 'anatoly-mikhaltsov-butterfly-wing-scale.jpg'
		const backgroundName = 'Background picture of a red-ish butterfly wing under microscope'
		cy.intercept('*/apps/theming/background/shipped').as('setBackground')

		// Select background
		cy.findByRole('button', { name: backgroundName, pressed: false })
			.click()
		cy.findByRole('button', { name: backgroundName, pressed: true })
			.should('be.visible')

		// Validate changed background and primary
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss('#a53c17', background, '#652e11'))
	})

	it('See the header NOT being inverted this time', function() {
		cy.waitUntil(() => navigationHeader.getNavigationEntries().find('img').then((el) => {
			let ret = true
			el.each(function() {
				ret = ret && window.getComputedStyle(this).filter === 'none'
			})
			return ret
		}))
	})
})

describe('User select a custom background', function() {
	const image = 'image.jpg'
	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.uploadFile(user, image, 'image/jpeg')
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Background and color/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Select a custom background', function() {
		cy.intercept('*/apps/theming/background/custom').as('setBackground')

		// Pick background
		cy.findByRole('button', { name: 'Custom background' }).click()
		cy.findByRole('dialog')
			.should('be.visible')
			.findAllByRole('row')
			.contains(image)
			.click()
		cy.findByRole('button', { name: 'Select background' }).click()

		// Wait for background to be set
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, 'apps/theming/background?v=', '#2f2221'))
	})
})

describe('User changes settings and reload the page', function() {
	const image = 'image.jpg'

	before(function() {
		cy.createRandomUser().then((user: User) => {
			cy.uploadFile(user, image, 'image/jpeg')
			cy.login(user)
		})
	})

	it('See the user background settings', function() {
		cy.visit('/settings/user/theming')
		cy.findByRole('heading', { name: /Background and color/ })
			.should('exist')
			.scrollIntoView()
	})

	it('Select a custom background', function() {
		cy.intercept('*/apps/theming/background/custom').as('setBackground')

		// Pick background
		cy.findByRole('button', { name: 'Custom background' }).click()
		cy.findByRole('dialog')
			.should('be.visible')
			.findAllByRole('row')
			.contains(image)
			.click()
		cy.findByRole('button', { name: 'Select background' }).click()

		// Wait for background to be set
		cy.wait('@setBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, 'apps/theming/background?v=', '#2f2221'))
	})

	it('Select a custom background color', function() {
		cy.intercept('*/apps/theming/background/color').as('clearBackground')

		// Clear background
		pickColor(cy.findByRole('button', { name: 'Plain background' }), 5)

		// Validate clear background
		cy.wait('@clearBackground')
		cy.waitUntil(() => validateBodyThemingCss(defaultPrimary, null, '#a5b872'))
	})

	it('Select a custom primary color', function() {
		cy.intercept('/ocs/v2.php/apps/provisioning_api/api/v1/config/users/theming/primary_color').as('setPrimaryColor')

		pickColor(cy.findByRole('button', { name: 'Primary color' }), 2)

		cy.wait('@setPrimaryColor')
		cy.waitUntil(() => validateBodyThemingCss('#c98879', null, '#a5b872'))
	})

	it('Reload the page and validate persistent changes', function() {
		cy.reload()
		cy.waitUntil(() => validateBodyThemingCss('#c98879', null, '#a5b872'))
	})
})
