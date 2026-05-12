/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { User } from '@nextcloud/e2e-test-server/cypress'
import { clearState, getNextcloudHeader } from '../../support/commonUtils.ts'

const getAppMenu = () => getNextcloudHeader().find('.app-menu')
// Both triggers share aria-label="Open apps menu", so getByRole can't
// disambiguate them. BEM classes owned by the component under test are
// the next-best stable selectors.
const getWaffleTrigger = () => getAppMenu().find('.app-menu__waffle')

describe('Header: App menu (waffle launcher)', { testIsolation: true }, () => {
	beforeEach(() => {
		clearState()
	})

	describe('Open and click', () => {
		beforeEach(() => {
			cy.createRandomUser().then(($user) => {
				cy.login($user)
				cy.visit('/')
			})
		})

		it('opens the popover and navigates when a tile is clicked', () => {
			getWaffleTrigger().click()
			cy.get('.app-menu__popover').should('be.visible')
			getWaffleTrigger().should('have.attr', 'aria-expanded', 'true')

			cy.findAllByRole('menuitem').first()
				.should('be.visible')
				.then(($tile) => {
					const href = $tile.attr('href')
					expect(href).to.match(/\/apps\//)
					cy.wrap($tile).click()
					cy.location('pathname').should('include', '/apps/')
				})
		})
	})

	describe('Admin gating: "More apps" tile', () => {
		const admin = new User('admin', 'admin')

		beforeEach(() => {
			cy.login(admin)
			cy.visit('/')
		})

		it('shows the "More apps" tile for admins', () => {
			getWaffleTrigger().click()
			cy.get('.app-menu__popover').should('be.visible')
			cy.findByRole('menuitem', { name: 'More apps' }).should('be.visible')
		})
	})
})
