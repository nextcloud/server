/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { getBodyThemingSnapshot, pickColor } from '../../support/utils/theming.ts'

test('User can configure background and plain color', async ({ page }) => {
	await page.goto('settings/user/theming')
	await page.getByRole('heading', { name: 'Background and color' }).waitFor({ state: 'visible' })

	await expect(page.getByRole('button', { name: 'Default background', pressed: true })).toBeVisible()

	const darkBackground = 'anatoly-mikhaltsov-butterfly-wing-scale.jpg'
	const darkBackgroundName = 'Background picture of a red-ish butterfly wing under microscope'
	await page.getByRole('button', { name: darkBackgroundName, pressed: false }).click()
	await expect(page.getByRole('button', { name: darkBackgroundName, pressed: true })).toBeVisible()
	await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toContain(darkBackground)

	const brightBackground = 'bernie-cetonia-aurata-take-off-composition.jpg'
	const brightBackgroundName = 'Montage of a cetonia aurata bug that takes off with white background'
	await page.getByRole('button', { name: brightBackgroundName, pressed: false }).click()
	await expect(page.getByRole('button', { name: brightBackgroundName, pressed: true })).toBeVisible()
	await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toContain(brightBackground)

	const plainBackgroundButton = page.getByRole('button', { name: 'Plain background' })
	await pickColor(page, plainBackgroundButton, 7)
	await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toBe('none')

	await page.reload()
	await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toBe('none')
})
