/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test as adminTest } from '../../support/fixtures/admin-session.ts'
import { ContactsMenuPage } from '../../support/sections/ContactsMenuPage.ts'

type ContactsFixtures = { contactUser: User }

// Extend the admin session with a fresh random user available as `contactUser`.
// The user enumeration config is also reset to the permissive default here so
// that tests that modify it cannot bleed across runs.
const test = adminTest.extend<ContactsFixtures>({
	contactUser: async ({}, use) => {
		await runOcc(['config:app:delete', 'core', 'shareapi_restrict_user_enumeration_to_group'])
		const user = await createRandomUser()
		await use(user)
		await runOcc(['user:delete', user.userId])
	},
})

// The restriction test toggles a global OCC config. Serial mode prevents
// parallel tests from racing on that setting.
test.describe.configure({ mode: 'serial' })

test.describe('Header: Contacts menu', () => {
	test('other users are seen in the contacts menu', async ({ page, contactUser }) => {
		await page.goto('/')
		const contactsMenu = new ContactsMenuPage(page)
		await contactsMenu.open()

		await expect(contactsMenu.contact(contactUser.userId)).toBeVisible()
		// The logged-in admin must not appear in their own contacts list.
		await expect(contactsMenu.contact('admin')).toHaveCount(0)
	})

	test('just-added users are seen in the contacts menu', async ({ page, contactUser }) => {
		// Create a second user directly in the test body; clean up with try/finally.
		const extraUser = await createRandomUser()
		try {
			await page.goto('/')
			const contactsMenu = new ContactsMenuPage(page)
			await contactsMenu.open()

			await expect(contactsMenu.contact(contactUser.userId)).toBeVisible()
			await expect(contactsMenu.contact(extraUser.userId)).toBeVisible()
			await expect(contactsMenu.contact('admin')).toHaveCount(0)
		} finally {
			await runOcc(['user:delete', extraUser.userId])
		}
	})

	test('search filters the contact list', async ({ page, contactUser }) => {
		const otherUser = await createRandomUser()
		try {
			await page.goto('/')
			const contactsMenu = new ContactsMenuPage(page)
			await contactsMenu.open()

			// Both users visible before searching.
			await expect(contactsMenu.contact(contactUser.userId)).toBeVisible()
			await expect(contactsMenu.contact(otherUser.userId)).toBeVisible()

			// Searching for otherUser hides contactUser.
			await contactsMenu.search(otherUser.userId)
			await expect(contactsMenu.contact(otherUser.userId)).toBeVisible()
			await expect(contactsMenu.contact(contactUser.userId)).toHaveCount(0)
			await expect(contactsMenu.contact('admin')).toHaveCount(0)
		} finally {
			await runOcc(['user:delete', otherUser.userId])
		}
	})

	test('searching for an unknown user shows no results', async ({ page, contactUser }) => {
		await page.goto('/')
		const contactsMenu = new ContactsMenuPage(page)
		await contactsMenu.open()

		await expect(contactsMenu.contact(contactUser.userId)).toBeVisible()

		await contactsMenu.search('surely-unknown-user')

		// NcEmptyContent renders the "name" prop as a heading.
		await expect(page.getByText('No contacts found', { exact: true })).toBeVisible()
		await expect(contactsMenu.contact(contactUser.userId)).toHaveCount(0)
		await expect(contactsMenu.contact('admin')).toHaveCount(0)
	})

	test('users from other groups are not seen when user enumeration is restricted to the same group', async ({ page, contactUser }) => {
		// Enable restriction first, then open the menu.
		await runOcc(['config:app:set', '--value', 'yes', 'core', 'shareapi_restrict_user_enumeration_to_group'])
		try {
			await page.goto('/')
			const contactsMenu = new ContactsMenuPage(page)
			await contactsMenu.open()

			// contactUser is in no group shared with admin → hidden.
			await expect(contactsMenu.contact(contactUser.userId)).toHaveCount(0)
			await expect(contactsMenu.contact('admin')).toHaveCount(0)

			// Close, lift the restriction, reopen — the contact should reappear.
			await runOcc(['config:app:set', '--value', 'no', 'core', 'shareapi_restrict_user_enumeration_to_group'])
			await contactsMenu.close()

			await page.reload()
			await contactsMenu.open()

			await expect(contactsMenu.contact(contactUser.userId)).toBeVisible()
			await expect(contactsMenu.contact('admin')).toHaveCount(0)
		} finally {
			await runOcc(['config:app:delete', 'core', 'shareapi_restrict_user_enumeration_to_group'])
		}
	})
})
