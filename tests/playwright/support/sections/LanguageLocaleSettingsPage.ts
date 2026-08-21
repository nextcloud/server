/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'
import { saveAccountProperty } from '../utils/account-properties.ts'
import { expectSelectedOption, pickSelectOption } from '../utils/select.ts'

/**
 * Page object for the personal "Language & locale" settings section.
 *
 * Saving the language or the locale reloads the page, so the result of such a
 * change is asserted on the reloaded page.
 */
export class LanguageLocaleSettingsPage {
	constructor(
		private readonly page: Page,
		private readonly user: User,
	) {}

	async open(): Promise<void> {
		await this.page.goto('settings/user/language-locale')
		await expect(this.page.getByRole('heading', { name: 'Language & locale', level: 2 })).toBeVisible()
	}

	languageSelect(): Locator {
		return this.page.getByRole('combobox', { name: 'Language' })
	}

	localeSelect(): Locator {
		return this.page.getByRole('combobox', { name: 'Locale' })
	}

	firstDayOfWeekSelect(): Locator {
		return this.page.getByRole('combobox', { name: 'First day of week' })
	}

	/** Date and time as rendered in the currently selected locale */
	localeExample(): Locator {
		return this.page.getByText('Example:')
	}

	/**
	 * Select a language, which reloads the page in that language.
	 *
	 * @param language - Name of the language, as listed in the select
	 * @param search - Search term to filter the languages with
	 */
	async selectLanguage(language: string, search: string): Promise<void> {
		await this.selectOption(this.languageSelect(), language, search)
	}

	/**
	 * Select a locale, which reloads the page using that locale.
	 *
	 * @param locale - Name of the locale, as listed in the select
	 * @param search - Search term to filter the locales with
	 */
	async selectLocale(locale: string, search: string): Promise<void> {
		await this.selectOption(this.localeSelect(), locale, search)
	}

	/**
	 * Select the day to use as the first day of week and wait for it to be persisted.
	 *
	 * @param day - Name of the day, as listed in the select
	 */
	async selectFirstDayOfWeek(day: string): Promise<void> {
		await saveAccountProperty(this.page, this.user.password, () => pickSelectOption(this.page, this.firstDayOfWeekSelect(), day))
	}

	/**
	 * Assert the option a select currently has selected.
	 *
	 * @param select - The select to read, see `languageSelect` and friends
	 * @param value - Text of the expected option
	 */
	async expectSelected(select: Locator, value: string | RegExp): Promise<void> {
		await expectSelectedOption(this.page, select, value)
	}

	private async selectOption(select: Locator, option: string, search?: string): Promise<void> {
		await pickSelectOption(this.page, select, option, search)
	}
}
