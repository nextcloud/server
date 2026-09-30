/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page, Response } from '@playwright/test'
import type { WorkflowRuleSection } from '../../support/sections/WorkflowSettingsPage.ts'
import type { OccSystemTag } from '../../support/utils/systemtags.ts'

import { expect, test } from '../../support/fixtures/workflow-settings-page.ts'
import { awaitPasswordGuardedRequest } from '../../support/utils/password-confirmation.ts'
import { expectSelectedOption } from '../../support/utils/select.ts'
import { createTag, deleteTag } from '../../support/utils/systemtags.ts'
import { listRules } from '../../support/utils/workflowengine.ts'

/**
 * The only operation this repository ships, from the files_versions app. Its
 * script registers the operation with the colour asserted below, which is what
 * proves that third-party registration reached the store.
 */
const FLOW = 'Block file versioning'
const FLOW_COLOR = 'rgb(255, 89, 0)'
const TRIGGER_HINT = 'A new version is created'

const WORKFLOWS_API = '/apps/workflowengine/api/v1/workflows/global'

/**
 * Save the flow and wait for the write to complete. Writing a flow is guarded
 * by a password confirmation, which may or may not be asked for depending on
 * how recently the session confirmed.
 *
 * @param page - The Playwright page object
 * @param rule - The flow being edited
 * @param method - Which write the save is expected to perform
 */
async function save(page: Page, rule: WorkflowRuleSection, method: 'POST' | 'PUT' = 'POST'): Promise<Response> {
	const response = page.waitForResponse((r) => r.url().includes(WORKFLOWS_API) && r.request().method() === method)
	await rule.saveButton().click()
	const result = await awaitPasswordGuardedRequest(page, response)
	// creating a flow replaces its list entry, and the outgoing one lingers for
	// the duration of the list transition
	await expect(rule.saveButton()).toHaveCount(1)
	return result
}

test.describe('Flow admin settings', () => {
	test('lists the installed flows and has none configured', async ({ workflowSettings }) => {
		await workflowSettings.open('admin')

		await expect(workflowSettings.availableFlowsHeading()).toBeVisible()
		await expect(workflowSettings.flowCard(FLOW)).toBeVisible()
		await expect(workflowSettings.developerDocsLink()).toBeVisible()

		// the colour comes from the operation the files_versions app registers
		await expect(workflowSettings.flowCardElement(FLOW)).toHaveCSS('background-color', FLOW_COLOR)

		// a single operation is installed, so the list is never truncated
		await expect(workflowSettings.showMoreButton()).toBeHidden()

		await expect(workflowSettings.configuredFlowsHeading()).toBeVisible()
		await expect(workflowSettings.noFlowsConfigured()).toBeVisible()
	})

	test('adds a flow and discards it again', async ({ workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)

		const rule = workflowSettings.rule()
		// the operation fixes its entity, so the trigger is not selectable
		await expect(rule.triggerHint(TRIGGER_HINT)).toBeVisible()
		await expect(rule.triggerCombobox()).toBeHidden()
		await expect(rule.filterCombobox()).toBeVisible()
		await expect(rule.saveButton()).toHaveAccessibleName('Save')

		await rule.cancelButton().click()
		await expect(workflowSettings.noFlowsConfigured()).toBeVisible()
	})

	test('creates a flow and reloads it', async ({ page, adminRequest, workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)

		const rule = workflowSettings.rule()
		await rule.selectFilter('File name')
		await rule.selectComparator('matches')
		await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')

		await rule.fillValue('/^secret-.+$/i')
		await expect(rule.saveButton()).toHaveAccessibleName('Save')

		const response = await save(page, rule)
		expect(response.status()).toBe(200)

		await expect(rule.saveButton()).toHaveAccessibleName('Active')
		await expect(rule.deleteButton()).toBeVisible()

		const stored = await listRules(adminRequest, 'global')
		expect(stored).toHaveLength(1)
		expect(stored[0].checks).toMatchObject([
			{ class: 'OCA\\WorkflowEngine\\Check\\FileName', operator: 'matches', value: '/^secret-.+$/i' },
		])

		await workflowSettings.open('admin')
		await expect(rule.saveButton()).toHaveAccessibleName('Active')
		await expect(rule.valueInput()).toHaveValue('/^secret-.+$/i')
		await expectSelectedOption(page, rule.filterCombobox(), /File name/)
		await expectSelectedOption(page, rule.comparatorCombobox(), /matches/)
	})

	test('validates the value of a filter before it can be saved', async ({ workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)
		const rule = workflowSettings.rule()

		await test.step('a file name that is not a regular expression', async () => {
			await rule.selectFilter('File name')
			await rule.selectComparator('matches')
			await rule.fillValue('/unterminated')
			await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')

			// the same value is a perfectly good literal file name
			await rule.selectComparator('is')
			await expect(rule.saveButton()).toHaveAccessibleName('Save')
		})

		await test.step('a file size that is not a size', async () => {
			await rule.selectFilter('File size (upload)')
			await rule.fillValue('five')
			await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')

			await rule.fillValue('5 MB')
			await expect(rule.saveButton()).toHaveAccessibleName('Save')
		})

		await test.step('a remote address that is not an address', async () => {
			await rule.selectFilter('Request remote address')
			await rule.selectComparator('matches IPv4')
			await rule.fillValue('127.0.0.1/32')
			await expect(rule.saveButton()).toHaveAccessibleName('Save')

			// the placeholder follows the comparator, and an IPv4 range is not IPv6
			await rule.selectComparator('matches IPv6')
			await expect(rule.valueInput()).toHaveAttribute('placeholder', '::1/128')
			await rule.fillValue('abc')
			await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')

			await rule.fillValue('::1/128')
			await expect(rule.saveButton()).toHaveAccessibleName('Save')
		})
	})

	test('collects several filters and removes one again', async ({ page, adminRequest, workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)
		const rule = workflowSettings.rule()

		// an empty filter row cannot be followed by another one
		await expect(rule.addFilterButton()).toBeHidden()

		await rule.selectFilter('File name')
		await rule.selectComparator('is')
		await rule.fillValue('first.txt')
		await expect(rule.addFilterButton()).toBeVisible()

		await rule.addFilter()
		await rule.selectFilter('File size (upload)', 1)
		await rule.fillValue('5 MB', 1)
		await expect(rule.saveButton()).toHaveAccessibleName('Save')

		await rule.removeFilter(0)
		await expect(rule.filterComboboxes()).toHaveCount(1)

		const response = await save(page, rule)
		expect(response.status()).toBe(200)

		const stored = await listRules(adminRequest, 'global')
		expect(stored[0].checks).toMatchObject([
			{ class: 'OCA\\WorkflowEngine\\Check\\FileSize', value: '5 MB' },
		])
	})

	test('reverts an edit and deletes a saved flow', async ({ page, adminRequest, workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)
		const rule = workflowSettings.rule()
		await rule.selectFilter('File name')
		await rule.selectComparator('is')
		await rule.fillValue('keep.txt')
		await save(page, rule)
		await expect(rule.saveButton()).toHaveAccessibleName('Active')

		await rule.fillValue('changed.txt')
		await expect(rule.saveButton()).toHaveAccessibleName('Save')
		await rule.cancelButton().click()
		await expect(rule.valueInput()).toHaveValue('keep.txt')
		await expect(rule.saveButton()).toHaveAccessibleName('Active')

		const deletion = page.waitForResponse((r) => r.url().includes(WORKFLOWS_API) && r.request().method() === 'DELETE')
		await rule.deleteButton().click()
		expect((await awaitPasswordGuardedRequest(page, deletion)).status()).toBe(200)

		await expect(workflowSettings.noFlowsConfigured()).toBeVisible()
		expect(await listRules(adminRequest, 'global')).toHaveLength(0)
	})

	test('reports why the server rejected a flow', async ({ page, workflowSettings }) => {
		await workflowSettings.open('admin')
		await workflowSettings.addFlow(FLOW)
		const rule = workflowSettings.rule()
		await rule.selectFilter('File name')
		await rule.selectComparator('is')
		await rule.fillValue('rejected.txt')

		const message = 'The given operation is invalid'
		await page.route(
			(url) => url.pathname.includes(WORKFLOWS_API),
			async (route) => {
				if (route.request().method() !== 'POST') {
					return await route.fallback()
				}
				await route.fulfill({
					status: 400,
					contentType: 'application/json',
					body: JSON.stringify({ ocs: { meta: { status: 'failure', statuscode: 400, message } } }),
				})
			},
		)

		await rule.saveButton().click()
		await expect(rule.errorMessage(message)).toBeVisible()
		await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')
	})

	test.describe('checks that render a custom element', () => {
		test('file MIME type', async ({ page, adminRequest, workflowSettings }) => {
			await workflowSettings.open('admin')
			await workflowSettings.addFlow(FLOW)
			const rule = workflowSettings.rule()
			await rule.selectFilter('File MIME type')

			await rule.fileTypeCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Images', { exact: true }) }).click()
			await save(page, rule)
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe('/image\\/.*/')

			await rule.fileTypeCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Custom MIME type', { exact: true }) }).click()
			// a literal type is not a regular expression, which the server rejects
			await rule.selectComparator('is')
			await rule.customValueInput('e.g. httpd/unix-directory').fill('text/plain')
			await save(page, rule, 'PUT')
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe('text/plain')

			await workflowSettings.open('admin')
			await expect(rule.customValueInput('e.g. httpd/unix-directory')).toHaveValue('text/plain')
		})

		test('file system tag', async ({ page, adminRequest, workflowSettings }) => {
			let tag: OccSystemTag | undefined
			try {
				tag = await createTag('flow-tag')

				await workflowSettings.open('admin')
				await workflowSettings.addFlow(FLOW)
				const rule = workflowSettings.rule()
				await rule.selectFilter('File system tag')

				await rule.tagCombobox().click()
				await page.getByRole('option').filter({ has: page.getByTitle(tag.name, { exact: true }) }).click()
				await save(page, rule)
				expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe(tag.id)

				await workflowSettings.open('admin')
				await expectSelectedOption(page, rule.tagCombobox(), new RegExp(tag.name))
			} finally {
				if (tag !== undefined) {
					await deleteTag(tag.id, true)
				}
			}
		})

		test('request URL', async ({ page, adminRequest, workflowSettings }) => {
			await workflowSettings.open('admin')
			await workflowSettings.addFlow(FLOW)
			const rule = workflowSettings.rule()
			await rule.selectFilter('Request URL')

			await rule.requestUrlCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Files WebDAV', { exact: true }) }).click()
			await save(page, rule)
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe('webdav')

			await rule.requestUrlCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Custom URL', { exact: true }) }).click()
			await rule.customValueInput('https://localhost/index.php').fill('https://example.com/dav')
			await save(page, rule, 'PUT')

			await workflowSettings.open('admin')
			await expect(rule.customValueInput('https://localhost/index.php')).toHaveValue('https://example.com/dav')
		})

		test('request user agent', async ({ page, adminRequest, workflowSettings }) => {
			await workflowSettings.open('admin')
			await workflowSettings.addFlow(FLOW)
			const rule = workflowSettings.rule()
			await rule.selectFilter('Request user agent')

			await rule.userAgentCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Android client', { exact: true }) }).click()
			await save(page, rule)
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe('android')

			await workflowSettings.open('admin')
			await expectSelectedOption(page, rule.userAgentCombobox(), /Android client/)
		})

		test('request time', async ({ page, adminRequest, workflowSettings }) => {
			await workflowSettings.open('admin')
			await workflowSettings.addFlow(FLOW)
			const rule = workflowSettings.rule()
			await rule.selectFilter('Request time')

			await rule.startTimeInput().fill('25:00')
			await rule.endTimeInput().fill('18:00')
			await expect(rule.invalidTimeSpanHint()).toBeVisible()
			await expect(rule.saveButton()).toHaveAccessibleName('The configuration is invalid')

			await rule.startTimeInput().fill('08:00')
			await expect(rule.invalidTimeSpanHint()).toBeHidden()
			await expect(rule.saveButton()).toHaveAccessibleName('Save')

			await rule.timezoneCombobox().click()
			await page.getByRole('option').filter({ has: page.getByTitle('Europe/Berlin', { exact: true }) }).click()
			await save(page, rule)
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value)
				.toBe('["08:00 Europe/Berlin","18:00 Europe/Berlin"]')

			await workflowSettings.open('admin')
			await expect(rule.startTimeInput()).toHaveValue('08:00')
			await expect(rule.endTimeInput()).toHaveValue('18:00')
			await expectSelectedOption(page, rule.timezoneCombobox(), /Europe\/Berlin/)
		})

		test('group membership', async ({ page, adminRequest, workflowSettings }) => {
			await workflowSettings.open('admin')
			await workflowSettings.addFlow(FLOW)
			const rule = workflowSettings.rule()
			await rule.selectFilter('Group membership')

			await rule.groupsCombobox().fill('admin')
			await page.getByRole('option').filter({ has: page.getByTitle('admin', { exact: true }) }).click()
			await save(page, rule)
			expect((await listRules(adminRequest, 'global'))[0].checks[0].value).toBe('admin')

			await workflowSettings.open('admin')
			await expectSelectedOption(page, rule.groupsCombobox(), /admin/)
		})
	})
})
