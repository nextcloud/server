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

before(clearState)

describe('Header: App menu (waffle launcher)', { testIsolation: true }, () => {
	describe('Normal user', () => {
		beforeEach(() => {
			cy.createRandomUser().then(($user) => {
				cy.login($user)
				cy.visit('/')
			})
		})

		it('Open and click opens the popover and navigates when a tile is clicked', () => {
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

		it('has all correct app navigation items', () => {
			waffleMenuShouldContainApps([
				{ name: 'Files', href: '/apps/files' },
				{ name: 'Dashboard', href: '/apps/dashboard' },
			])
		})
	})

	describe('Admin', () => {
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

		it('has all correct app navigation items', () => {
			waffleMenuShouldContainApps([
				{ name: 'Files', href: '/apps/files' },
				{ name: 'Dashboard', href: '/apps/dashboard' },
				{ name: 'Appstore', href: '/settings/apps' },
			])
		})
	})
})

/**
 * Check that the waffle menu contains the given apps, by name and href.
 *
 * @param apps - The apps that should be present in the waffle menu, with their expected name and href.
 */
function waffleMenuShouldContainApps(apps: { name: string, href: string }[]) {
	getWaffleTrigger().click()
	getWaffleTrigger().should('have.attr', 'aria-expanded', 'true')
	cy.findByRole('menu', { name: 'Apps' }).should('be.visible')

	cy.findAllByRole('menuitem')
		.then((items) => {
			apps.forEach((app) => {
				const item = items.toArray().find((i) => i.textContent?.includes(app.name))
				expect(item, `App menu should contain ${app.name}`).to.exist
				expect(item?.getAttribute('href')).to.match(new RegExp(`${app.href}(\\?.+|/?$)`))
			})
		})
}
