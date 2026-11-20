/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-session.ts'
import { pickSelectOption } from '../../support/utils/select.ts'

const CONFIG_KEYS = [
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
 * Go back to the defaults of the testing app's form
 */
async function resetValues(): Promise<void> {
	for (const key of CONFIG_KEYS) {
		await runOcc(['config:app:delete', 'testing', key])
	}
}

/**
 * Run an action and wait until the form saved the changed value
 *
 * @param page - The Playwright page object
 * @param action - The change to the form
 */
async function save(page: Page, action: () => Promise<void>): Promise<void> {
	const response = page.waitForResponse((r) => r.url().includes('/settings/api/declarative/value') && r.request().method() === 'POST')
	await action()
	expect((await response).ok()).toBe(true)
}

test.describe('Settings: declarative forms', () => {
	// The testing app registers a declarative form with every field type
	test.beforeAll(async () => {
		await runOcc(['app:enable', 'testing'])
		// a run that was cut short leaves its values behind
		await resetValues()
	})

	test.afterAll(async () => {
		// each test changes its own field, so cleaning up once is enough
		await resetValues()
		await runOcc(['app:disable', 'testing'])
	})

	test.beforeEach(async ({ page }) => {
		await page.goto('settings/admin/additional')
		await expect(page.getByRole('heading', { name: 'Test declarative settings class' })).toBeVisible()
	})

	test('saves a text field and shows it after reload', async ({ page }) => {
		await save(page, () => page.getByRole('textbox', { name: 'Default text field' }).fill('Hello'))
		expect(await savedValue('test_ex_app_field_1')).toBe('Hello')

		await page.reload()
		await expect(page.getByRole('textbox', { name: 'Default text field' })).toHaveValue('Hello')
	})

	test('saves a select', async ({ page }) => {
		await save(page, () => pickSelectOption(page, page.getByRole('combobox', { name: 'Selection', exact: true }), 'baz'))
		expect(await savedValue('test_ex_app_field_3')).toBe('baz')
	})

	test('saves a multi-select', async ({ page }) => {
		await save(page, () => pickSelectOption(page, page.getByRole('combobox', { name: 'Multi-selection' }), 'baz'))
		expect(JSON.parse(await savedValue('test_ex_app_field_7'))).toEqual(['foo', 'bar', 'baz'])
	})

	test('saves a switch', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await save(page, () => page.getByRole('switch', { name: 'Verify something if enabled' }).check({ force: true }))
		expect(await savedValue('test_ex_app_field_4')).toBe('1')
	})

	test('saves a group of checkboxes', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await save(page, () => page.getByRole('checkbox', { name: 'Qux' }).check({ force: true }))
		expect(JSON.parse(await savedValue('test_ex_app_field_5'))).toMatchObject({ qux: true })
	})

	test('saves a radio group', async ({ page }) => {
		// NcCheckboxRadioSwitch hides the native input behind its own styling
		await save(page, () => page.getByRole('radio', { name: 'Second radio' }).check({ force: true }))
		expect(await savedValue('test_ex_app_field_6')).toBe('bar')
	})

	test('stores a sensitive field encrypted', async ({ page }) => {
		await save(page, () => page.getByRole('textbox', { name: 'Sensitive text field' }).fill('top secret'))

		const stored = await savedValue('test_sensitive_field')
		expect(stored).not.toBe('')
		expect(stored).not.toContain('top secret')
	})
})
