/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Page object model for the Nextcloud navigation header.
 *
 * The app launcher (waffle menu) is an NcPopover whose content is teleported
 * to <body>, so the menu items do not live inside the <nav> element. Selectors
 * for the menu entries scope to the popover rather than the nav.
 */
export class NavigationHeader {
	/**
	 * Locator of the header bar wrapper
	 */
	header() {
		return cy.get('header#header')
	}

	/**
	 * Locator for the logo navigation entry (entry redirects to default app)
	 */
	logo() {
		return this.header()
			.find('#nextcloud')
	}

	/**
	 * Locator of the app navigation bar.
	 *
	 * The accessible name is just "Applications" since the waffle redesign;
	 * the previous label "Applications menu" is gone.
	 */
	navigation() {
		return this.header()
			.findByRole('navigation', { name: 'Applications' })
	}

	/**
	 * Open the waffle launcher popover.
	 *
	 * Idempotent: if the popover is already open the click is skipped, so
	 * callers can invoke this defensively at the start of any helper that
	 * needs the menu items in the DOM.
	 */
	openMenu() {
		this.navigation()
			.find('.app-menu__waffle')
			.then(($trigger) => {
				if ($trigger.attr('aria-expanded') !== 'true') {
					cy.wrap($trigger).click()
				}
			})
		// Popover is teleported to <body>, so query from the document root.
		cy.get('.app-menu__popover').should('be.visible')
		return this.popover()
	}

	/**
	 * Close the waffle launcher popover.
	 *
	 * Sends Escape rather than clicking outside: NcPopover's focus trap is
	 * active while the menu is open, so a stray click can land on a tile.
	 */
	closeMenu() {
		cy.get('body').type('{esc}')
		cy.get('.app-menu__popover').should('not.exist')
	}

	/**
	 * Locator for the popover content (the teleported grid wrapper).
	 *
	 * Scoping menu-item queries here is mandatory: the popover is rendered
	 * outside the <nav>, so `.within(navigation())` would find nothing.
	 */
	popover() {
		return cy.get('[role="menu"][aria-label="Apps"]')
	}

	/**
	 * The waffle trigger that toggles the launcher.
	 *
	 * @deprecated The old "overflow" affordance is gone; this now points at
	 *   the waffle button so existing call sites keep compiling. Prefer
	 *   {@link openMenu} / {@link closeMenu} in new code.
	 */
	overflowNavigationToggle() {
		return this.navigation()
			.find('.app-menu__waffle')
	}

	/**
	 * Get all navigation entries in the launcher.
	 *
	 * Opens the popover first if it is not already open; the entries do not
	 * exist in the DOM otherwise. Each entry is rendered as an `<a role="menuitem">`.
	 */
	getNavigationEntries() {
		this.openMenu()
		return this.popover().findAllByRole('menuitem')
	}

	/**
	 * Get the navigation entry for a given app.
	 *
	 * Each tile's accessible name comes from the `<a title="...">` attribute
	 * and the inner `.app-item__label`, so `findByRole('menuitem', { name })`
	 * matches reliably.
	 *
	 * @param name The app name
	 */
	getNavigationEntry(name: string) {
		this.openMenu()
		return this.popover().findByRole('menuitem', { name })
	}
}
