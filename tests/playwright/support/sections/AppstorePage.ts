/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

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
		await this.page.waitForURL(/settings\/apps\/bundles$/)
	}

	/**
	 * Gets the apps table element
	 */
	appsTable(): Locator {
		return this.page.getByRole('table')
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
	 * Gets the app link in the table
	 */
	appLink(appName: string): Locator {
		return this.appsTable().getByRole('link', { name: appName })
	}

	/**
	 * Gets the navigation link in the appstore sidebar
	 */
	navigationLink(name: string): Locator {
		return this.page.getByRole('navigation', { name: 'Appstore categories' }).getByRole('link', { name })
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
		return this.appSidebar().getByRole('button', { name: 'Enable' })
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
		return this.appSidebar().getByRole('button', { name: 'Remove' })
	}

	/**
	 * Gets the "Limit to groups" button
	 */
	limitToGroupsButton(): Locator {
		return this.appSidebar().getByRole('button', { name: 'Limit to groups' })
	}

	/**
	 * Gets the "Limited to groups" list
	 */
	limitedToGroupsList(): Locator {
		return this.appSidebar().getByRole('list', { name: 'Limited to groups' })
	}

	/**
	 * Gets the group dialog
	 */
	groupDialog(): Locator {
		return this.page.getByRole('dialog')
	}

	/**
	 * Gets the save button in the dialog
	 */
	dialogSaveButton(): Locator {
		return this.groupDialog().getByRole('button', { name: 'Save' })
	}

	/**
	 * Gets the deselect button for a group
	 */
	deselectGroupButton(groupName: string): Locator {
		return this.groupDialog().getByRole('button', { name: `Deselect ${groupName}` })
	}

	/**
	 * Gets the group search input.
	 * NcSelectUsers uses role="combobox" on the search input, not role="textbox".
	 */
	groupSearchInput(): Locator {
		return this.groupDialog().locator('input').first()
	}

	/**
	 * Gets the enterprise bundle heading
	 */
	enterpriseBundleHeading(): Locator {
		return this.page.getByRole('heading', { name: 'Enterprise bundle' })
	}

	/**
	 * Gets the education bundle heading
	 */
	educationBundleHeading(): Locator {
		return this.page.getByRole('heading', { name: 'Education bundle' })
	}

	/**
	 * Gets the version text from sidebar
	 */
	versionText(): Locator {
		return this.appSidebar().getByText(/Version \d+\.\d+\.\d+/)
	}

	/**
	 * Gets a group option from the dropdown
	 */
	groupOption(groupName: string): Locator {
		return this.page.getByRole('option', { name: new RegExp(groupName) })
	}
}
