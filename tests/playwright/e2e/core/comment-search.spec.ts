/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { test } from '../../support/fixtures/random-user-session.ts'
import { UnifiedSearchPage } from '../../support/sections/UnifiedSearchPage.ts'
import { mkdir, uploadContent } from '../../support/utils/dav.ts'

test('opens a file found by its comment', async ({ page, user }) => {
	await mkdir(page.request, user, '/comment-regression')
	const image = readFileSync(resolve(process.cwd(), 'tests/data/images/image.jpg'))
	const fileId = await uploadContent(
		page.request,
		user,
		image,
		'image/jpeg',
		'/comment-regression/image.jpg',
	)
	expect(Number(fileId)).toBeGreaterThan(0)

	const tokenResponse = await page.request.get('/csrftoken')
	expect(tokenResponse.ok()).toBe(true)
	const { token } = await tokenResponse.json()
	const response = await page.request.post(`/remote.php/dav/comments/files/${fileId}/`, {
		headers: { requesttoken: token },
		data: { actorType: 'users', verb: 'comment', message: 'uniquesearchcomment' },
	})
	expect(response.status()).toBe(201)

	await page.goto('apps/files')
	const search = new UnifiedSearchPage(page)
	await search.input().fill('uniquesearchcomment')
	await search.waitForSettledResults()
	const result = search.options().filter({ hasText: 'comment-regression/image.jpg' })
	await expect(result).toHaveCount(1)
	await result.getByRole('link').click()

	const viewer = page.getByRole('dialog', { name: 'image.jpg' })
	await expect(viewer).toBeVisible()
	await expect(viewer.getByRole('img', { name: 'image.jpg' })).toBeVisible({ timeout: 15_000 })
})
