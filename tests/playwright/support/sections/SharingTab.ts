/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

/** A share-editor request against the OCS Share API. */
const SHARES_API = '/apps/files_sharing/api/v1/shares'

/**
 * The permission bundles the editor offers as radio buttons, keyed by the bundle
 * the product calls them internally. Their accessible name is the visible label;
 * "Allow upload and editing" degrades to "Allow editing" where a file drop is
 * not possible (i.e. anything but a public folder share).
 */
const PERMISSION_BUNDLE_NAMES = {
	'read-only': 'View only',
	'upload-edit': /^Allow (upload and )?editing$/,
	'file-drop': 'File request',
	custom: 'Custom permissions',
} as const

export type PermissionBundle = keyof typeof PERMISSION_BUNDLE_NAMES

/**
 * The "Sharing" tab of the Files right sidebar — the share editor.
 *
 * The tab lists the existing shares and hosts the editor for a single share
 * ("details view"), which is where permissions, expiration, notes and the
 * public-link options live. Everything is addressed by role and accessible name;
 * the editor labels its controls, so no product-owned `data-cy` hooks are needed.
 *
 * Use {@link open} to get here (the sidebar must be open, e.g. via the row's
 * "details" or "sharing-status" action).
 */
export class SharingTab {
	constructor(private readonly page: Page) {}

	/** The sidebar hosting the tab. */
	private sidebar(): Locator {
		return this.page.locator('#app-sidebar-vue')
	}

	/** The Sharing tab panel. */
	panel(): Locator {
		return this.page.getByRole('tabpanel', { name: 'Sharing' })
	}

	/**
	 * Select the Sharing tab in the already-open sidebar and wait for the share
	 * list to be fetched, so the entries are present before any assertion runs.
	 *
	 * The sidebar remembers the last tab a user was on, so the tab can already be
	 * selected — clicking it again would neither refetch nor change anything.
	 * Readiness is therefore taken from the tab's own content: the recipient input
	 * is rendered only once the shares have loaded.
	 */
	async open(): Promise<void> {
		const tab = this.sidebar().getByRole('tab', { name: 'Sharing' })
		if (await tab.getAttribute('aria-selected') !== 'true') {
			await tab.click()
		}
		await expect(this.panel()).toBeVisible()
		await expect(this.recipientInput()).toBeVisible()
	}

	/**
	 * The recipient search input. It is an NcSelect, whose `role="combobox"` sits
	 * on the input itself; its label is visually hidden but present.
	 */
	recipientInput({ external = false }: { external?: boolean } = {}): Locator {
		return this.panel().getByRole('combobox', {
			name: external ? 'Enter external recipients' : 'Search for internal recipients',
		})
	}

	/**
	 * Search for a recipient and pick it from the dropdown, returning nothing once
	 * the share is staged in the editor (not yet saved — call {@link save}).
	 *
	 * The sharee lookup is a server round trip, so the request is awaited before
	 * the option is picked; the listbox is teleported to the body, so the option
	 * is matched at page level. Never force-click the option: that races the
	 * dropdown opening and the pick silently does not register.
	 *
	 * @param recipient - The user id / email to search for
	 * @param options - `external` searches the email/remote recipient field instead
	 */
	async pickRecipient(recipient: string, { external = false }: { external?: boolean } = {}): Promise<void> {
		const found = this.page.waitForResponse((r) => r.url().includes('/apps/files_sharing/api/v1/sharees'))
		await this.recipientInput({ external }).fill(recipient)
		await found
		await this.page.getByRole('option', { name: new RegExp(recipient, 'i') }).first().click()
	}

	/** The list of internal (user/group) shares. */
	private internalShares(): Locator {
		return this.panel().getByRole('list', { name: 'Shares' })
	}

	/** The list of public link shares. */
	linkShares(): Locator {
		return this.panel().getByRole('list', { name: 'Link shares' })
	}

	/** The link share entries, newest last. */
	linkShareEntries(): Locator {
		return this.linkShares().getByRole('listitem')
	}

	/**
	 * Create a new public link share through the editor and return its URL.
	 *
	 * With no enforced password or expiration date the share is created straight
	 * away; otherwise the button only opens a menu with the required fields and
	 * the caller has to confirm it with {@link confirmPendingLinkShare}.
	 */
	async createLinkShare(): Promise<string> {
		const created = this.page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes(SHARES_API))
		await this.panel().getByRole('button', { name: 'Create a new share link' }).click()
		return this.shareUrlFrom(created)
	}

	/**
	 * The popover a link share opens instead of creating itself right away, when
	 * the instance defaults still require a password or an expiration date. It
	 * holds form fields, so NcActions exposes it as a dialog rather than a menu.
	 */
	pendingShareDialog(): Locator {
		return this.page.getByRole('dialog', { name: /Actions for/ })
	}

	/**
	 * Confirm the pending-share dialog and return the created share's URL.
	 */
	async confirmPendingLinkShare(): Promise<string> {
		const created = this.page.waitForResponse((r) => r.request().method() === 'POST' && r.url().includes(SHARES_API))
		await this.pendingShareDialog().getByRole('button', { name: 'Create share' }).click()
		return this.shareUrlFrom(created)
	}

	/** Read the share URL out of a create-share response. */
	private async shareUrlFrom(response: Promise<{ json: () => Promise<unknown> }>): Promise<string> {
		const body = await (await response).json() as { ocs?: { data?: { url?: string } } }
		const url = body.ocs?.data?.url
		expect(url, 'the created share should carry a public URL').toMatch(/^https?:\/\//)
		return url!
	}

	/**
	 * Open the editor for the internal (user or group) share at `index`.
	 *
	 * @param index - The position in the share list (0 = first)
	 */
	async openShareDetails(index = 0): Promise<void> {
		const entries = this.internalShares().getByRole('listitem')
		await expect(entries).not.toHaveCount(0)
		await entries.nth(index).getByRole('button', { name: 'Open Sharing Details' }).click()
		await expect(this.saveButton()).toBeVisible()
	}

	/**
	 * Open the editor for the link share at `index` via its actions menu
	 * ("Customize link").
	 *
	 * @param index - The position in the link share list (0 = first)
	 */
	async openLinkShareDetails(index = 0): Promise<void> {
		const entry = this.linkShareEntries().nth(index)
		await entry.getByRole('button', { name: /Actions/i }).click()
		await this.page.getByRole('menuitem', { name: /Customize link/i }).click()
		await expect(this.saveButton()).toBeVisible()
	}

	/** A permission bundle radio in the open editor. */
	permissionBundle(bundle: PermissionBundle): Locator {
		return this.panel().getByRole('radio', { name: PERMISSION_BUNDLE_NAMES[bundle] })
	}

	/**
	 * Pick a permission bundle in the open editor. The radio itself is visually
	 * hidden inside its button-styled label, so the label is clicked instead of
	 * the input (a forced check on the input can land outside the viewport).
	 *
	 * @param bundle - The bundle to select
	 */
	async selectPermissionBundle(bundle: PermissionBundle): Promise<void> {
		const radio = this.permissionBundle(bundle)
		await radio.scrollIntoViewIfNeeded()
		await radio.check({ force: true })
		await expect(radio).toBeChecked()
	}

	/** Expand the editor's "Advanced settings" section. */
	async openAdvancedSettings(): Promise<void> {
		const toggle = this.panel().getByRole('button', { name: /Advanced settings/i })
		if (await toggle.getAttribute('aria-expanded') !== 'true') {
			await toggle.click()
		}
		await expect(toggle).toHaveAttribute('aria-expanded', 'true')
	}

	/**
	 * A checkbox in the open editor, by its visible label (e.g. "Hide download",
	 * "Note to recipient", "Show files in grid view", "Allow download and sync").
	 */
	checkbox(name: string | RegExp): Locator {
		return this.panel().getByRole('checkbox', { name })
	}

	/**
	 * Toggle one of the editor's checkboxes. They are `NcCheckboxRadioSwitch`es
	 * whose real input is visually hidden, hence the forced check plus an explicit
	 * state assertion.
	 *
	 * @param name - The checkbox label
	 * @param checked - The state to set
	 */
	async setCheckbox(name: string | RegExp, checked: boolean): Promise<void> {
		const box = this.checkbox(name)
		await box.scrollIntoViewIfNeeded()
		await box.setChecked(checked, { force: true })
		await expect(box).toBeChecked({ checked })
	}

	/** The note-to-recipient text area (only rendered once its checkbox is on). */
	noteInput(): Locator {
		return this.panel().getByRole('textbox', { name: 'Note to recipient' })
	}

	/** The share label input in the advanced section (public shares only). */
	labelInput(): Locator {
		return this.panel().getByRole('textbox', { name: 'Share label' })
	}

	/**
	 * The editor's expiration date input. It is a native date input, which has no
	 * ARIA role of its own, so it is addressed by its (hidden) label.
	 */
	expirationDateInput(): Locator {
		return this.panel().getByLabel('Expiration date', { exact: true })
	}

	/**
	 * The pending-share dialog's expiration date input — the one shown before a
	 * link share with an enforced or defaulted expiration date is created.
	 */
	pendingExpirationDateInput(): Locator {
		return this.pendingShareDialog().getByRole('textbox', { name: /Enter expiration date/ })
	}

	/**
	 * The editor's save button. Its label states what will happen: "Save share"
	 * for a new share, "Update share" for an existing one.
	 */
	private saveButton(): Locator {
		return this.panel().getByRole('button', { name: /^(Save|Update|Create) share$/ })
	}

	/**
	 * Save the open editor and wait for the share request to land, returning its
	 * parsed OCS payload so callers can assert on the stored share (e.g. its
	 * effective permissions).
	 */
	async save(): Promise<{ id: number, permissions: number, url?: string }> {
		const saved = this.page.waitForResponse((r) => ['POST', 'PUT'].includes(r.request().method())
			&& r.url().includes(SHARES_API))
		await this.saveButton().click()
		const body = await (await saved).json() as { ocs: { data: { id: number, permissions: number, url?: string } } }
		return body.ocs.data
	}

	/**
	 * The editor's cancel button.
	 */
	private cancelButton(): Locator {
		return this.panel().getByRole('button', { name: 'Cancel' })
	}

	/**
	 * Leave the open editor without saving the changes.
	 * Returns once the button is no longer visible and therefore the share list
	 * is back.
	 */
	async cancel(): Promise<void> {
		await this.cancelButton().click()
		await expect(this.cancelButton()).toBeHidden()
	}
}
