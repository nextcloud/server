/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { pickSelectOption } from '../utils/select.ts'

/** Which flow settings page to work with. */
export type WorkflowSettingsScope = 'admin' | 'personal'

/**
 * The states a flow reports through the label of its save button, which
 * therefore doubles as that button's accessible name.
 */
export const RuleStatus = {
	SAVE: 'Save',
	ACTIVE: 'Active',
	INVALID: 'The configuration is invalid',
} as const

export type RuleStatusLabel = typeof RuleStatus[keyof typeof RuleStatus]

const ANY_RULE_STATUS = new RegExp(`^(${Object.values(RuleStatus).join('|')})$`)

/**
 * One configured flow: its trigger, its filter rows and the buttons that
 * persist, revert or delete it.
 *
 * The specs configure a single flow at a time, so filter rows are addressed by
 * their position among all filter rows of the page.
 */
export class WorkflowRuleSection {
	constructor(private readonly page: Page) {}

	/** The flows are rendered in the settings content area. */
	private main(): Locator {
		return this.page.getByRole('main')
	}

	/**
	 * The static trigger description of a flow whose operation fixes its entity.
	 * Flows with a selectable trigger expose {@link triggerCombobox} instead.
	 */
	triggerHint(hint: string | RegExp): Locator {
		return this.main().getByText(hint)
	}

	triggerCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'Trigger' })
	}

	/** The filter combobox of every filter row, in document order. */
	filterComboboxes(): Locator {
		return this.main().getByRole('combobox', { name: 'Filter', exact: true })
	}

	filterCombobox(index = 0): Locator {
		return this.filterComboboxes().nth(index)
	}

	comparatorCombobox(index = 0): Locator {
		return this.main().getByRole('combobox', { name: 'Comparator' }).nth(index)
	}

	/**
	 * The plain text value input of a filter row. Its accessible name is the
	 * placeholder the check provides, which changes with the comparator. Checks
	 * that ship a custom element render their own controls instead — reach those
	 * through the dedicated getters below.
	 */
	valueInput(index = 0): Locator {
		return this.main().getByRole('textbox').nth(index)
	}

	async selectFilter(name: string, index = 0): Promise<void> {
		await pickSelectOption(this.page, this.filterCombobox(index), name)
	}

	async selectComparator(name: string, index = 0): Promise<void> {
		await pickSelectOption(this.page, this.comparatorCombobox(index), name)
	}

	async fillValue(value: string, index = 0): Promise<void> {
		await this.valueInput(index).fill(value)
	}

	/**
	 * Only offered once the last filter row carries a filter, so a flow can
	 * never collect two empty rows.
	 */
	addFilterButton(): Locator {
		return this.main().getByRole('button', { name: 'Add a new filter' })
	}

	async addFilter(): Promise<void> {
		await this.addFilterButton().click()
	}

	removeFilterButton(index = 0): Locator {
		return this.main().getByRole('button', { name: 'Remove filter' }).nth(index)
	}

	/**
	 * Remove a filter row. The button only renders while the row counts as
	 * active, which a click on the row establishes.
	 *
	 * @param index - Position of the filter row to remove
	 */
	async removeFilter(index = 0): Promise<void> {
		await this.filterCombobox(index).click()
		await this.page.keyboard.press('Escape')
		await this.removeFilterButton(index).click()
	}

	saveButton(): Locator {
		return this.main().getByRole('button', { name: ANY_RULE_STATUS })
	}

	cancelButton(): Locator {
		return this.main().getByRole('button', { name: 'Cancel' })
	}

	deleteButton(): Locator {
		return this.main().getByRole('button', { name: 'Delete' })
	}

	/** The message the server answered a rejected save or delete with. */
	errorMessage(message: string | RegExp): Locator {
		return this.main().getByText(message)
	}

	/* Controls of the shipped checks that render a custom element. */

	fileTypeCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'File type' })
	}

	tagCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'Tag' })
	}

	requestUrlCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'Request URL' })
	}

	userAgentCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'User agent' })
	}

	groupsCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'Select groups' })
	}

	startTimeInput(): Locator {
		return this.main().getByRole('textbox', { name: 'Start time' })
	}

	endTimeInput(): Locator {
		return this.main().getByRole('textbox', { name: 'End time' })
	}

	timezoneCombobox(): Locator {
		return this.main().getByRole('combobox', { name: 'Timezone' })
	}

	/**
	 * The free text input a "predefined option or custom value" check reveals
	 * while its select sits on the custom entry.
	 *
	 * @param placeholder - The placeholder that input carries
	 */
	customValueInput(placeholder: string): Locator {
		return this.main().getByPlaceholder(placeholder)
	}

	invalidTimeSpanHint(): Locator {
		return this.main().getByText('Please enter a valid time span')
	}
}

/**
 * The flow settings (Settings → Administration → Flow, and the personal
 * equivalent).
 */
export class WorkflowSettingsPage {
	constructor(private readonly page: Page) {}

	private main(): Locator {
		return this.page.getByRole('main')
	}

	/**
	 * Open the page for a scope and wait for the flows to be rendered.
	 *
	 * @param scope - Whether to open the instance-wide or the personal page
	 */
	async open(scope: WorkflowSettingsScope = 'admin'): Promise<void> {
		await this.page.goto(scope === 'admin' ? 'settings/admin/workflow' : 'settings/user/workflow')
		await this.availableFlowsHeading().waitFor({ state: 'visible' })
	}

	/** Carries the documentation link, so its accessible name is not just the title. */
	availableFlowsHeading(): Locator {
		return this.main().getByRole('heading', { name: 'Available flows' })
	}

	/** Named "Configured flows" for an admin and "Your flows" for a user. */
	configuredFlowsHeading(): Locator {
		return this.main().getByRole('heading', { name: /^(Configured flows|Your flows)$/ })
	}

	developerDocsLink(): Locator {
		return this.main().getByRole('link', { name: /development documentation/ })
	}

	/** The name of an installed flow, as its card presents it. */
	flowCard(name: string): Locator {
		return this.main().getByRole('heading', { name, level: 3 })
	}

	/**
	 * The card element around a flow name, which carries the operation colour.
	 * Cards have no role of their own, so it is reached from the heading: the
	 * heading sits in the card's description block, which sits in the card.
	 *
	 * @param name - Name of the flow whose card to address
	 */
	flowCardElement(name: string): Locator {
		return this.flowCard(name).locator('xpath=../..')
	}

	/**
	 * The button of a flow card that starts configuring a new flow.
	 *
	 * Cards are plain containers, so the button is addressed within the page.
	 * This repository ships a single operation, so exactly one card is rendered.
	 */
	addFlowButton(): Locator {
		return this.main().getByRole('button', { name: 'Add new flow' })
	}

	/**
	 * Start configuring a new flow of the given operation.
	 *
	 * @param name - Name of the flow to add
	 */
	async addFlow(name: string): Promise<void> {
		await this.flowCard(name).waitFor({ state: 'visible' })
		await this.addFlowButton().click()
	}

	showMoreButton(): Locator {
		return this.main().getByRole('button', { name: /^(Show more|Show less)$/ })
	}

	/** Empty states render as a note, not as a heading. */
	noFlowsInstalled(): Locator {
		return this.main().getByRole('note', { name: 'No flows installed' })
	}

	askAdministratorHint(): Locator {
		return this.main().getByText('Ask your administrator to install new flows.')
	}

	noFlowsConfigured(): Locator {
		return this.main().getByRole('note', { name: 'No flows configured' })
	}

	/** The flow currently being configured. */
	rule(): WorkflowRuleSection {
		return new WorkflowRuleSection(this.page)
	}
}
