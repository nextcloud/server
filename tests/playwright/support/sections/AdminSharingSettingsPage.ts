/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page, Response } from '@playwright/test'

import { expect } from '@playwright/test'
import { pickSelectOption } from '../utils/select.ts'

/** The roles the boolean settings of the section are rendered with. */
export type ToggleRole = 'switch' | 'checkbox' | 'radio'

/**
 * The admin "Sharing" settings section of the settings app
 * (Settings → Administration → Sharing).
 *
 * Other apps add their own sections to the same page, so every control is
 * matched by its exact accessible name.
 */
export class AdminSharingSettingsPage {
	constructor(private readonly page: Page) {}

	async open(): Promise<void> {
		await this.page.goto('settings/admin/sharing')
		await this.heading().waitFor({ state: 'visible' })
	}

	heading(): Locator {
		return this.page.getByRole('heading', { level: 2, name: /^Sharing\b/ })
	}

	/** The section's link to the admin documentation. */
	documentationLink(): Locator {
		return this.heading().getByRole('link', { name: 'External documentation' })
	}

	/** The warning shown instead of the settings while the sharing app is disabled. */
	sharingAppDisabledNote(): Locator {
		return this.page.getByText('You need to enable the File sharing App.')
	}

	/**
	 * A boolean setting of the section.
	 *
	 * @param role - Whether the setting is a switch, a checkbox or a radio button
	 * @param name - Its label
	 */
	toggle(role: ToggleRole, name: string): Locator {
		return this.page.getByRole(role, { name, exact: true })
	}

	/**
	 * Check or uncheck a boolean setting.
	 *
	 * The input is visually hidden behind its label, so the click is forced.
	 *
	 * @param toggle - The switch, checkbox or radio button
	 * @param checked - The state to set
	 */
	async setChecked(toggle: Locator, checked: boolean): Promise<void> {
		await toggle.scrollIntoViewIfNeeded()
		await toggle.setChecked(checked, { force: true })
		await expect(toggle).toBeChecked({ checked })
	}

	/**
	 * Main switch of the section, all other sharing settings depend on it.
	 */
	shareApiSwitch(): Locator {
		return this.toggle('switch', 'Allow apps to use the Share API')
	}

	/**
	 * A checkbox of the default share permissions.
	 *
	 * @param name - The permission, e.g. "Create" or "Reshare"
	 */
	defaultPermission(name: 'Create' | 'Change' | 'Delete' | 'Reshare'): Locator {
		return this.toggle('checkbox', name)
	}

	/**
	 * A number input of the section.
	 *
	 * @param name - Its label
	 */
	numberInput(name: string): Locator {
		return this.page.getByRole('spinbutton', { name, exact: true })
	}

	disclaimerInput(): Locator {
		return this.page.getByRole('textbox', { name: 'Disclaimer text', exact: true })
	}

	/**
	 * The search input of a group select.
	 *
	 * @param label - Label of the select
	 */
	groupSelect(label: string): Locator {
		return this.page.getByRole('combobox', { name: label })
	}

	/**
	 * Add a group to a group select.
	 *
	 * @param label - Label of the select
	 * @param group - Display name of the group to add
	 */
	async addGroup(label: string, group: string): Promise<void> {
		await pickSelectOption(this.page, this.groupSelect(label), group, group)
		await this.page.keyboard.press('Escape')
	}

	/**
	 * A group shown as selected in one of the group selects. Each select renders
	 * its selected groups as tags with a deselect button.
	 *
	 * @param group - Display name of the group
	 */
	selectedGroup(group: string): Locator {
		return this.page.getByRole('button', { name: `Deselect ${group}`, exact: true })
	}

	/**
	 * Wait for a sharing setting to be written to the app config of core.
	 *
	 * Set it up before triggering the change.
	 *
	 * @param key - The config key, e.g. `shareapi_allow_links`
	 * @param value - The value to wait for, any value if omitted
	 */
	waitForConfigUpdate(key: string, value?: string): Promise<Response> {
		return this.page.waitForResponse((response) => {
			const request = response.request()
			return request.method() === 'POST'
				&& new URL(response.url()).pathname.endsWith(`/apps/provisioning_api/api/v1/config/apps/core/${key}`)
				&& (value === undefined || request.postDataJSON()?.value === value)
		})
	}

	/**
	 * Wait for a sharing setting to be removed from the app config of core.
	 *
	 * Set it up before triggering the change.
	 *
	 * @param key - The config key
	 */
	waitForConfigDeletion(key: string): Promise<Response> {
		return this.page.waitForResponse((response) => response.request().method() === 'DELETE'
			&& new URL(response.url()).pathname.endsWith(`/apps/provisioning_api/api/v1/config/apps/core/${key}`))
	}
}
