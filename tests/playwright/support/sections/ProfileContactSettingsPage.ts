/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { saveAccountProperty, waitForVisibilitySave } from '../utils/account-properties.ts'
import { isSelected } from '../utils/select.ts'

/** Federation scopes as rendered in the UI */
export const Scope = {
	Private: 'Private',
	Local: 'Local',
	Federated: 'Federated',
	Published: 'Published',
} as const
export type Scope = typeof Scope[keyof typeof Scope]

/** Profile visibility levels as rendered in the UI */
export const Visibility = {
	Everyone: 'Show to everyone',
	LoggedIn: 'Show to logged in accounts only',
	Hidden: 'Hide',
} as const
export type Visibility = typeof Visibility[keyof typeof Visibility]

/**
 * Page object for the personal "Profile & contact" settings section.
 *
 * Account properties are saved with a debounce, so every mutating method waits
 * for the request that persists the change - and for the password confirmation
 * dialog, which the server only asks for if the session login is not recent.
 */
export class ProfileContactSettingsPage {
	constructor(
		private readonly page: Page,
		private readonly user: User,
	) {}

	/** Heading of the settings section, to confirm the page has loaded */
	heading(): Locator {
		return this.page.getByRole('heading', { name: 'Profile & contact', level: 2 })
	}

	async open(): Promise<void> {
		await this.page.goto('settings/user/profile-contact')
		await expect(this.heading()).toBeVisible()
	}

	async reload(): Promise<void> {
		await this.page.reload()
	}

	// ── Text properties ─────────────────────────────────────────────────────────

	/**
	 * Input of an account property, addressed by its visible label,
	 * e.g. "Full name", "Phone number" or "Fediverse (e.g. Mastodon)".
	 *
	 * @param label - Label of the property
	 */
	property(label: string): Locator {
		return this.page.getByRole('textbox', { name: label })
	}

	/**
	 * Set an account property and wait for it to be persisted.
	 *
	 * @param label - Label of the property
	 * @param value - Value to save, an empty value resets the property
	 */
	async setProperty(label: string, value: string): Promise<void> {
		await saveAccountProperty(this.page, this.user.password, () => this.property(label).fill(value))
	}

	// ── Visibility and scope ────────────────────────────────────────────────────

	/**
	 * Trigger of the combined visibility and scope control, used by all
	 * properties which are shown on the profile.
	 *
	 * @param readable - Readable name of the property, e.g. "Full name"
	 */
	visibilityAndScopeButton(readable: string): Locator {
		return this.page.getByRole('button', { name: `Change visibility and scope of ${readable}` })
	}

	/**
	 * Trigger of the scope only control, used by the email addresses and by
	 * properties that are no profile field, like the date of birth. Its
	 * accessible name also states the current scope.
	 *
	 * @param readable - Readable name of the property, e.g. "Date of birth"
	 * @param scope - Expected current scope, to assert on the rendered state
	 */
	scopeButton(readable: string, scope?: Scope): Locator {
		const name = scope === undefined
			? `Change scope level of ${readable}`
			: `Change scope level of ${readable}, current scope is ${scope}`
		return this.page.getByRole('button', { name })
	}

	private scopeSelect(): Locator {
		return this.page.getByRole('combobox', { name: 'Scope' })
	}

	private visibilitySelect(): Locator {
		return this.page.getByRole('combobox', { name: 'Visibility' })
	}

	private scopeListbox(): Locator {
		return this.page.getByRole('listbox', { name: 'Scope' })
	}

	private visibilityListbox(): Locator {
		return this.page.getByRole('listbox', { name: 'Visibility' })
	}

	private popoverHeading(): Locator {
		return this.page.getByRole('heading', { name: 'Visibility & Scope' })
	}

	/**
	 * Option of the scope select. Options are labelled by their name and their
	 * description, so they are matched by text and not by accessible name.
	 *
	 * @param scope - Name of the scope
	 */
	private scopeOption(scope: Scope): Locator {
		return this.scopeListbox().getByRole('option').filter({ hasText: scope })
	}

	/**
	 * Option of the visibility select.
	 *
	 * @param visibility - Name of the visibility level
	 */
	private visibilityOption(visibility: Visibility): Locator {
		return this.visibilityListbox().getByRole('option').filter({ hasText: visibility })
	}

	/**
	 * Open the combined control. The popover focuses the visibility select, so
	 * its options are shown right away.
	 *
	 * @param readable - Readable name of the property, e.g. "Full name"
	 */
	private async openVisibilityAndScope(readable: string): Promise<void> {
		await this.visibilityAndScopeButton(readable).scrollIntoViewIfNeeded()
		await this.visibilityAndScopeButton(readable).click()
		await expect(this.popoverHeading()).toBeVisible()
		await expect(this.visibilitySelect()).toBeFocused()
		await expect(this.visibilityListbox()).toBeVisible()
	}

	/**
	 * Move on to the scope select, which shows its options once focused.
	 *
	 * The options of the visibility select cover it as long as they are shown, so
	 * the focus is moved by keyboard instead of clicking the select.
	 */
	private async focusScopeSelect(): Promise<void> {
		await this.page.keyboard.press('Tab')
		await expect(this.scopeSelect()).toBeFocused()
		await expect(this.scopeListbox()).toBeVisible()
	}

	/** Close the popover, and the options of the focused select if still shown */
	private async closeVisibilityAndScope(): Promise<void> {
		await this.page.keyboard.press('Escape')
		if (await this.popoverHeading().isVisible()) {
			await this.page.keyboard.press('Escape')
		}
		await expect(this.popoverHeading()).toBeHidden()
	}

	/**
	 * Change the federation scope of a property using the combined control.
	 *
	 * @param readable - Readable name of the property, e.g. "Full name"
	 * @param scope - Scope to set
	 */
	async setScope(readable: string, scope: Scope): Promise<void> {
		await this.openVisibilityAndScope(readable)
		await this.focusScopeSelect()
		if (await isSelected(this.scopeOption(scope))) {
			// Picking the current option does not trigger a save
			await this.closeVisibilityAndScope()
			return
		}
		await saveAccountProperty(this.page, this.user.password, () => this.scopeOption(scope).click())
		await this.closeVisibilityAndScope()
	}

	/**
	 * Change the profile visibility of a property using the combined control.
	 *
	 * @param readable - Readable name of the property, e.g. "Headline"
	 * @param visibility - Visibility to set
	 */
	async setVisibility(readable: string, visibility: Visibility): Promise<void> {
		await this.openVisibilityAndScope(readable)
		if (await isSelected(this.visibilityOption(visibility))) {
			// Picking the current option does not trigger a save
			await this.closeVisibilityAndScope()
			return
		}
		await saveAccountProperty(this.page, this.user.password, () => this.visibilityOption(visibility).click(), waitForVisibilitySave)
		await this.closeVisibilityAndScope()
	}

	/**
	 * Assert the scope the combined control currently reflects.
	 *
	 * @param readable - Readable name of the property, e.g. "Full name"
	 * @param scope - Expected scope
	 */
	async expectScope(readable: string, scope: Scope): Promise<void> {
		await this.openVisibilityAndScope(readable)
		await this.focusScopeSelect()
		// The selected value is only exposed as such while the options are shown
		await expect(this.scopeListbox().getByRole('option', { selected: true })).toContainText(scope)
		await this.closeVisibilityAndScope()
	}

	/**
	 * Assert the profile visibility the combined control currently reflects.
	 *
	 * @param readable - Readable name of the property, e.g. "Headline"
	 * @param visibility - Expected visibility
	 */
	async expectVisibility(readable: string, visibility: Visibility): Promise<void> {
		await this.openVisibilityAndScope(readable)
		// The selected value is only exposed as such while the options are shown
		await expect(this.visibilityListbox().getByRole('option', { selected: true })).toContainText(visibility)
		await this.closeVisibilityAndScope()
	}

	/**
	 * Change the federation scope of a property using the scope only control.
	 *
	 * @param readable - Readable name of the property, e.g. "Date of birth"
	 * @param scope - Scope to set
	 */
	async setScopeLevel(readable: string, scope: Scope): Promise<void> {
		await saveAccountProperty(this.page, this.user.password, async () => {
			await this.scopeButton(readable).click()
			await this.page.getByRole('menuitemradio', { name: scope }).click()
		})
	}

	// ── Profile ─────────────────────────────────────────────────────────────────

	profileSwitch(): Locator {
		return this.page.getByRole('switch', { name: 'Nextcloud profile' })
	}

	/**
	 * Enable or disable the profile and wait for it to be persisted.
	 *
	 * @param enabled - Whether the profile should be enabled
	 */
	async setProfileEnabled(enabled: boolean): Promise<void> {
		await saveAccountProperty(this.page, this.user.password, async () => {
			await this.profileSwitch().setChecked(enabled, { force: true })
		})
	}

	// ── Email addresses ─────────────────────────────────────────────────────────

	primaryEmail(): Locator {
		return this.property('Primary email address')
	}

	/**
	 * Input of an additional email address.
	 *
	 * @param position - Position of the address, starting at 1
	 */
	additionalEmail(position: number): Locator {
		return this.property(`Additional email address ${position}`)
	}

	addAdditionalEmailButton(): Locator {
		return this.page.getByRole('button', { name: 'Additional address' })
	}

	/**
	 * Delete action of the primary email address.
	 *
	 * As long as no address is set the row has no scope control, so its only
	 * action is rendered as a button instead of an entry of the options menu.
	 */
	removePrimaryEmailButton(): Locator {
		return this.page.getByRole('button', { name: 'Remove primary email' })
	}

	/**
	 * Add an empty additional email address entry.
	 */
	async addAdditionalEmail(): Promise<void> {
		await this.addAdditionalEmailButton().click()
	}

	/**
	 * Set an email address and wait for it to be persisted.
	 *
	 * @param input - Input of the address to fill, see `primaryEmail` and `additionalEmail`
	 * @param email - Address to save
	 */
	async setEmail(input: Locator, email: string): Promise<void> {
		await saveAccountProperty(this.page, this.user.password, () => input.fill(email))
	}

	/**
	 * Options menu of an email address row.
	 *
	 * Every row uses the scope control of the email property as its options menu,
	 * so all rows share the same accessible name and are addressed by position.
	 *
	 * @param position - Position of the row, 0 being the primary address
	 */
	emailOptions(position = 0): Locator {
		return this.scopeButton('email').nth(position)
	}

	/**
	 * Delete an email address through the options menu of its row.
	 *
	 * @param position - Position of the row, 0 being the primary address
	 */
	async deleteEmail(position = 0): Promise<void> {
		await this.emailOptions(position).click()
		const action = position === 0 ? 'Remove primary email' : 'Delete email'
		await saveAccountProperty(this.page, this.user.password, async () => {
			await this.page.getByRole('menuitem', { name: action }).click()
		})
	}
}
