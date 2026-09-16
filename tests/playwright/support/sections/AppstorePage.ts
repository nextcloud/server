/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

/**
 * Page object for the app management pages of the settings app (/settings/apps).
 *
 * Selector strategy:
 * - Prefer role / label / text selectors.
 * - The app list container (`#apps-list`) and the sidebar (`#app-sidebar-vue`)
 *   have no accessible name, so they are addressed by id.
 * - The sidebar action buttons are `<input type="button">` carrying an
 *   `aria-label` tooltip, so their accessible name is not the button caption —
 *   they are matched on their `value` instead.
 */
export class AppstorePage {
	constructor(private readonly page: Page) {}

	/**
	 * Opens the main appstore page
	 */
	async openAppstore() {
		await this.page.goto('settings/apps')
		await this.appsTable().waitFor({ state: 'visible' })
	}

	/**
	 * Opens the installed apps page
	 */
	async openInstalledApps() {
		await this.page.goto('settings/apps/installed')
		await this.appsTable().waitFor({ state: 'visible' })
	}

	/**
	 * Opens the enabled apps page
	 */
	async openEnabledApps() {
		await this.navigationLink('Active apps').click()
		await this.page.waitForURL(/settings\/apps\/enabled$/)
	}

	/**
	 * Opens the disabled apps page
	 */
	async openDisabledApps() {
		await this.navigationLink('Disabled apps').click()
		await this.page.waitForURL(/settings\/apps\/disabled$/)
	}

	/**
	 * Opens the app bundles page
	 */
	async openBundles() {
		await this.navigationLink('App bundles').click()
		await this.page.waitForURL(/settings\/apps\/app-bundles$/)
	}

	/**
	 * Gets the app list container
	 */
	appsList(): Locator {
		return this.page.locator('#apps-list')
	}

	/**
	 * Gets the apps table element
	 */
	appsTable(): Locator {
		return this.appsList().locator('table')
	}

	/**
	 * Gets a specific app row by app name
	 */
	appRow(appName: string): Locator {
		return this.appsTable().locator('tr').filter({ hasText: appName }).first()
	}

	/**
	 * Gets the enable button for a specific app
	 */
	enableButton(appName: string): Locator {
		return this.appRow(appName).getByRole('button', { name: 'Enable' })
	}

	/**
	 * Gets the disable button for a specific app
	 */
	disableButton(appName: string): Locator {
		return this.appRow(appName).getByRole('button', { name: 'Disable' })
	}

	/**
	 * Gets the app link in the table.
	 * The link is labelled "Show details for {appName} app".
	 */
	appLink(appName: string): Locator {
		return this.appsTable().getByRole('link', { name: appName })
	}

	/**
	 * Gets the navigation link in the appstore sidebar
	 */
	navigationLink(name: string): Locator {
		return this.page.getByRole('navigation', { name: 'Apps' }).getByRole('link', { name })
	}

	/**
	 * Gets the row header of an app bundle
	 */
	bundleHeader(name: string): Locator {
		return this.appsTable().getByRole('rowheader', { name })
	}

	/**
	 * Gets the app sidebar
	 */
	appSidebar(): Locator {
		return this.page.locator('#app-sidebar-vue')
	}

	/**
	 * Gets the app sidebar header
	 */
	appSidebarHeader(): Locator {
		return this.appSidebar().locator('.app-sidebar-header__info')
	}

	/**
	 * Gets the "Enable" button in the app sidebar (not the table row).
	 * Use this when checking the sidebar after clicking an app link.
	 */
	appSidebarEnableButton(): Locator {
		return this.appSidebar().locator('input[type="button"][value="Enable"]')
	}

	/**
	 * Gets the "View in store" link in the sidebar
	 */
	viewInStoreLink(): Locator {
		return this.appSidebar().getByRole('link', { name: 'View in store' })
	}

	/**
	 * Gets the "Remove" button in the sidebar
	 */
	removeButton(): Locator {
		return this.appSidebar().locator('input[type="button"][value="Remove"]')
	}

	/**
	 * Gets the "Limit to groups" checkbox of an app.
	 * The input itself is visually hidden, so it is toggled through its label.
	 *
	 * @param appId - The app id, not the app name
	 */
	limitToGroupsCheckbox(appId: string): Locator {
		return this.appSidebar().locator(`#groups_enable_${appId}`)
	}

	/**
	 * Gets the label toggling the "Limit to groups" checkbox
	 *
	 * @param appId - The app id, not the app name
	 */
	limitToGroupsLabel(appId: string): Locator {
		return this.appSidebar().locator(`label[for="groups_enable_${appId}"]`)
	}

	/**
	 * Gets the search input of the group select.
	 * NcSelect puts `role="combobox"` on the inner input.
	 */
	groupSearchInput(): Locator {
		return this.appSidebar().locator('#limitToGroups')
	}

	/**
	 * Gets the deselect button of an already selected group
	 */
	deselectGroupButton(groupName: string): Locator {
		return this.appSidebar().getByRole('button', { name: `Deselect ${groupName}` })
	}

	/**
	 * Gets a group option from the teleported dropdown
	 */
	groupOption(groupName: string): Locator {
		return this.page.getByRole('option', { name: new RegExp(groupName) })
	}

	/**
	 * Gets the version text from sidebar
	 */
	versionText(): Locator {
		return this.appSidebar().getByText(/Version \d+\.\d+\.\d+/)
	}
}
