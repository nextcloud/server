/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../../support/fixtures/random-user-session.ts'
import { BackgroundFilePickerDialogPage } from '../../support/sections/BackgroundFilePickerDialogPage.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'
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

test('User can pick a custom background from their files', {
	annotation: { type: 'issue', description: 'https://github.com/nextcloud/server/issues/58645' },
}, async ({ page, user }) => {
	await mkdir(page.request, user, '/folder')

	// this is a minimal image (1x1 red pixel), encoded as base64
	const imageBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4AWL6z8DwHwAAAP//A3ONEwAAAAZJREFUAwAFCgIByRpMngAAAABJRU5ErkJggg=='
	// Buffer.alloc(0) did not work when selecting image as background, using base64 image instead
	await uploadContent(page.request, user, Buffer.from(imageBase64, 'base64'), 'image/jpeg', '/folder/image.jpg')

	await page.goto('settings/user/theming')
	await page.getByRole('heading', { name: 'Background and color' }).waitFor({ state: 'visible' })

	await page.getByRole('button', { name: 'Custom background' }).click()

	const filePicker = new BackgroundFilePickerDialogPage(page)
	await filePicker.openFolder('folder')
	await filePicker.selectFile('image.jpg')
	await filePicker.confirm()

	await expect(page.getByRole('button', { name: 'Custom background', pressed: true })).toBeVisible()
	// backgroundImage is like this: "url(\"<nc-instance>/apps/theming/background?v=<hash>\")"
	await expect.poll(async () => (await getBodyThemingSnapshot(page)).backgroundImage).toContain('/apps/theming/background?')
})
