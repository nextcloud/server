/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Page } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser } from '@nextcloud/e2e-test-server/playwright'
import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/admin-with-user.ts'
import { SettingsUsersPage } from '../../support/sections/SettingsUsersPage.ts'

/** Public API other apps (e.g. guests) use to add entries to the account actions menu. */
interface UserListApi {
	registerAction(
		icon: string,
		text: string,
		action: (event: MouseEvent, user: { id: string }) => void,
		enabled?: (user: { id: string }) => boolean,
	): void
}

const actionText = 'Run external action'

/**
 * Open the account list and register an action the way another app would.
 *
 * @param page The page to use
 * @param enabledFor The only account the action is enabled for
 * @return The ids of the accounts the action has been triggered for
 */
async function openWithExternalAction(page: Page, enabledFor: string): Promise<string[]> {
	const triggeredFor: string[] = []
	await page.exposeFunction('onExternalAction', (userId: string) => triggeredFor.push(userId))

	await new SettingsUsersPage(page).open()

	await page.evaluate(({ text, userId }) => {
		const { OCA, onExternalAction } = window as unknown as {
			OCA: { Settings: { UserList: UserListApi } }
			onExternalAction: (id: string) => void
		}
		OCA.Settings.UserList.registerAction(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2 2 22h20z"/></svg>',
			text,
			(event, account) => onExternalAction(account.id),
			(account) => account.id === userId,
		)
	}, { text: actionText, userId: enabledFor })

	return triggeredFor
}

test.describe('Settings: Account actions registered by other apps', () => {
	test('runs the action with the account it was triggered for', async ({ page, user }) => {
		const triggeredFor = await openWithExternalAction(page, user.userId)

		await new SettingsUsersPage(page).openActionsMenu(user.userId)
		await page.getByRole('menuitem', { name: actionText }).click()

		await expect.poll(() => triggeredFor).toEqual([user.userId])
	})

	test('hides the action for accounts it is not enabled for', async ({ page, user }) => {
		// Created before the list loads so its row is rendered
		const otherUser = await createRandomUser()
		try {
			await openWithExternalAction(page, user.userId)
			const settingsPage = new SettingsUsersPage(page)

			await settingsPage.openActionsMenu(user.userId)
			await expect(page.getByRole('menuitem', { name: actionText })).toBeVisible()
			await page.keyboard.press('Escape')

			await settingsPage.openActionsMenu(otherUser.userId)
			await expect(page.getByRole('menuitem', { name: 'Disable account' })).toBeVisible()
			await expect(page.getByRole('menuitem', { name: actionText })).toHaveCount(0)
		} finally {
			await runOcc(['user:delete', otherUser.userId], { failOnError: false })
		}
	})
})
