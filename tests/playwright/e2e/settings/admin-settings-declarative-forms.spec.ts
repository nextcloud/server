/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { pickSelectOption } from '../../support/utils/select.ts'

// Config keys of the testing app's declarative form fields used below
const KEYS = [
	'test_ex_app_field_1',
	'test_ex_app_field_3',
	'test_ex_app_field_4',
	'test_ex_app_field_5',
	'test_ex_app_field_6',
	'test_ex_app_field_7',
	'test_sensitive_field',
]

/**
 * @param key - Config key of the testing app
 */
async function savedValue(key: string): Promise<string> {
	const { stdout } = await runOcc(['config:app:get', 'testing', key, '--default-value='])
	return stdout.trim()
}

/**
 * The combobox of a select field. Its label points at the select wrapper and
 * not at the combobox, so the combobox has no accessible name to look it up by.
 *
 * @param page - The Playwright page object
 * @param fieldId - Id of the form field
 */
function select(page: Page, fieldId: string): Locator {
	return page.locator(`[id="${fieldId}_field"]`).getByRole('combobox')
}

test.describe('Settings: declarative forms', () => {
	// The testing app registers a declarative form with every field type
	test.beforeAll(async () => {
		await runOcc(['app:enable', 'testing'])
	})

	test.afterAll(async () => {
		await runOcc(['app:disable', 'testing'])
	})

	test.afterEach(async () => {
		for (const key of KEYS) {
			await runOcc(['config:app:delete', 'testing', key])
		}
	})

	test.beforeEach(async ({ page }) => {
		await page.goto('settings/admin/additional')
		await expect(page.getByRole('heading', { name: 'Test declarative settings class' })).toBeVisible()
	})

	test('saves a text field and shows it after reload', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Default text field' }).fill('Hello')
		await expect.poll(() => savedValue('test_ex_app_field_1')).toBe('Hello')

		await page.reload()
		await expect(page.getByRole('textbox', { name: 'Default text field' })).toHaveValue('Hello')
	})

	test('saves a select', async ({ page }) => {
		await pickSelectOption(page, select(page, 'test_ex_app_field_3'), 'baz')
		await expect.poll(() => savedValue('test_ex_app_field_3')).toBe('baz')
	})

	test('saves a multi-select', async ({ page }) => {
		await pickSelectOption(page, select(page, 'test_ex_app_field_7'), 'baz')
		await expect.poll(() => savedValue('test_ex_app_field_7')).toContain('baz')
	})

	test('saves a switch', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await save(page, () => page.getByRole('switch', { name: 'Verify something if enabled' }).check({ force: true }))
		expect(await savedValue('test_ex_app_field_4')).toBe('1')
	})

	test('saves a group of checkboxes', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await page.getByRole('checkbox', { name: 'Qux' }).check({ force: true })
		await expect.poll(async () => JSON.parse(await savedValue('test_ex_app_field_5') || '{}')).toMatchObject({ qux: true })
	})

	test('saves a radio group', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await page.getByRole('radio', { name: 'Second radio' }).check({ force: true })
		await expect.poll(() => savedValue('test_ex_app_field_6')).toBe('bar')
	})

	test('stores a sensitive field encrypted', async ({ page }) => {
		await page.getByRole('textbox', { name: 'Sensitive text field' }).fill('top secret')

		await expect.poll(() => savedValue('test_sensitive_field')).not.toBe('')
		expect(await savedValue('test_sensitive_field')).not.toContain('top secret')
	})
})
