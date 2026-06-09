/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

export const defaultPrimary = '#00679e'
export const defaultBackground = 'jo-myoung-hee-fluid.webp'

export async function getBodyThemingSnapshot(page: Page) {
	return page.evaluate(() => {
		const styles = getComputedStyle(document.body)
		return {
			primary: styles.getPropertyValue('--color-primary').trim(),
			backgroundColor: styles.backgroundColor,
			backgroundImage: styles.backgroundImage,
		}
	})
}

export async function expectBodyThemingCss(page: Page, expected: {
	primary?: string
	background?: string | null
	backgroundColor?: string | null
}) {
	await expect.poll(async () => {
		const snapshot = await getBodyThemingSnapshot(page)
		const expectedPrimary = expected.primary ?? defaultPrimary
		const normalizedPrimary = await normalizeColor(page, snapshot.primary)
		const normalizedExpectedPrimary = await normalizeColor(page, expectedPrimary)

		const expectedBackgroundColor = expected.backgroundColor ?? defaultPrimary
		const normalizedBackground = expectedBackgroundColor === null
			? null
			: await normalizeColor(page, expectedBackgroundColor)

		const expectedBackground = expected.background === undefined ? defaultBackground : expected.background

		const validPrimary = normalizedPrimary === normalizedExpectedPrimary
		const validBackgroundColor = normalizedBackground === null || snapshot.backgroundColor === normalizedBackground
		const validBackgroundImage = expectedBackground === null
			? snapshot.backgroundImage === 'none'
			: snapshot.backgroundImage.includes(expectedBackground)

		return validPrimary && validBackgroundColor && validBackgroundImage
	}, {
		timeout: 10000,
		message: 'Expected body theming CSS to match expected values',
	}).toBeTruthy()
}

export async function expectPrimaryColor(page: Page, expectedColor: string) {
	const normalizedExpectedPrimary = await normalizeColor(page, expectedColor)

	await expect.poll(async () => {
		const snapshot = await getBodyThemingSnapshot(page)
		return normalizeColor(page, snapshot.primary)
	}, {
		timeout: 10000,
		message: 'Expected primary color CSS variable to match',
	}).toBe(normalizedExpectedPrimary)
}

export async function pickColor(page: Page, trigger: Locator, index: number) {
	const oldColor = await trigger.evaluate((element) => getComputedStyle(element as HTMLElement).backgroundColor)

	await trigger.click({ force: true })
	await page.locator('.color-picker__simple-color-circle').nth(index).click()
	await page.getByRole('button', { name: /Choose/i }).click()

	await expect.poll(async () => {
		return trigger.evaluate((element) => getComputedStyle(element as HTMLElement).backgroundColor)
	}).not.toBe(oldColor)

	return trigger.evaluate((element) => getComputedStyle(element as HTMLElement).backgroundColor)
}

async function normalizeColor(page: Page, color: string) {
	return page.evaluate((value) => {
		const element = document.createElement('div')
		element.style.color = value
		document.body.append(element)
		const normalized = getComputedStyle(element).color
		element.remove()
		return normalized
	}, color)
}
