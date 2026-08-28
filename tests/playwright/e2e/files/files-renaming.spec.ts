/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from '../../support/fixtures/files-page.ts'
import { mkdir, rm, uploadContent } from '../../support/utils/dav.ts'

test.describe('Files: Rename nodes', () => {
	test.beforeEach(async ({ page, user, filesListPage }) => {
		// New users get welcome.txt — remove it so the list contains only our test files
		await rm(page.request, user, '/welcome.txt')
		await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', '/file.txt')
		await filesListPage.open()
	})

	test('can rename a file', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('file.txt')
		await expect(input).toBeVisible()
		await input.fill('other.txt')
		await expect(input).toHaveValidationMessage('')
		await input.press('Enter')

		await expect(filesListPage.getRowForFile('other.txt')).toBeVisible()
	})

	/**
	 * If this test gets flaky then the selection is not reliably set to the basename.
	 * The selection should cover only the name part (without extension) when rename opens.
	 */
	test('only selects basename of file on rename open', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('file.txt')
		await expect(input).toBeVisible()

		const { selectionStart, selectionEnd } = await input.evaluate((el) => ({ selectionStart: (el as HTMLInputElement).selectionStart, selectionEnd: (el as HTMLInputElement).selectionEnd }))
		expect(selectionStart).toBe(0)
		expect(selectionEnd).toBe('file'.length)
	})

	test('shows validation error on invalid filename', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('file.txt')
		await expect(input).toBeVisible()
		await input.fill('.htaccess')

		await expect(input).toHaveValidationMessage(/reserved name/i)
	})

	test('shows accessible loading state while rename MOVE is in-flight', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		// Hold MOVE requests until we explicitly release them
		let resolveMove!: () => void
		const moveAllowed = new Promise<void>((resolve) => {
			resolveMove = resolve
		})
		await page.route(/remote\.php\/dav\/files\//, async (route) => {
			if (route.request().method() === 'MOVE') {
				await moveAllowed
			}
			await route.continue()
		})

		await filesListPage.triggerActionForFile('file.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('file.txt')
		await input.fill('new-name.txt')
		await input.press('Enter')

		// While MOVE is blocked: row shows loading icon, checkbox is hidden
		const loadingRow = filesListPage.getRowForFile('new-name.txt')
		await expect(loadingRow.getByRole('img', { name: 'File is loading' })).toBeVisible()
		await expect(loadingRow.getByRole('checkbox', { name: /Toggle selection/ })).not.toBeVisible()

		// Release the MOVE and wait for it to complete
		const moveResponse = page.waitForResponse((r) => r.url().includes('/remote.php/dav/files/') && r.request().method() === 'MOVE')
		resolveMove()
		await moveResponse
		await page.unroute(/remote\.php\/dav\/files\//)

		// Loading state clears: checkbox reappears, loading icon gone
		await expect(loadingRow.getByRole('checkbox', { name: /Toggle selection/ })).toBeVisible()
		await expect(loadingRow.getByRole('img', { name: 'File is loading' })).not.toBeVisible()
	})

	test('cancel renaming on Escape', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('file.txt')
		await expect(input).toBeVisible()
		await input.fill('other.txt')
		await expect(input).toHaveValidationMessage('')
		await input.press('Escape')

		// Original name kept, rename input removed
		await expect(filesListPage.getRowForFile('other.txt')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt').locator('input[type="text"]')).not.toBeVisible()
	})

	test('cancel renaming on Enter when name is unchanged', async ({ filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')

		const input = filesListPage.getRenameInputForFile('file.txt')
		await expect(input).toBeVisible()
		await input.press('Enter')

		// No rename happened, input is gone
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt').locator('input[type="text"]')).not.toBeVisible()
	})

	/**
	 * Regression: https://github.com/nextcloud/server/issues/47438
	 * Virtual scrolling removed the renaming component from DOM before state reset,
	 * leaving the row permanently stuck in rename mode.
	 */
	test('correctly resets renaming state after virtual-scroll re-render', async ({ page, user, filesListPage }) => {
		// Create 19 more files so virtual scrolling kicks in with a small viewport
		for (let i = 1; i <= 19; i++) {
			await uploadContent(page.request, user, Buffer.alloc(0), 'text/plain', `/file${i}.txt`)
		}

		// Start with a small viewport so only a few rows fit
		await page.setViewportSize({ width: 768, height: 500 })
		await filesListPage.open()

		// Measure the DOM to calculate the exact height that shows only 4 rows
		const viewportHeight = await page.evaluate(() => {
			const filesList = document.querySelector('[data-cy-files-list]') as HTMLElement
			const outerHeight = window.innerHeight - filesList.clientHeight
			const beforeHeight = (document.querySelector('.files-list__before') as HTMLElement)?.offsetHeight ?? 0
			const filterHeight = (document.querySelector('.files-list__filters') as HTMLElement)?.offsetHeight ?? 0
			const theadHeight = (document.querySelector('[data-cy-files-list-thead]') as HTMLElement)?.offsetHeight ?? 0
			const rowHeight = (document.querySelector('[data-cy-files-list-tbody] tr') as HTMLElement)?.offsetHeight ?? 0
			return outerHeight + beforeHeight + filterHeight + theadHeight + 4 * rowHeight
		})
		await page.setViewportSize({ width: 768, height: viewportHeight })
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		// Rename to 'zzz.txt' — sorts last, scrolls out of the visible area
		await filesListPage.triggerActionForFile('file.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('file.txt')
		const moveResponse = page.waitForResponse((r) => r.url().includes('/remote.php/dav/files/') && r.request().method() === 'MOVE')
		await input.fill('zzz.txt')
		await input.press('Enter')
		await moveResponse

		// After rename zzz.txt is sorted to the end — no longer in the visible viewport
		await expect(filesListPage.getRowForFile('zzz.txt')).toHaveCount(0)

		// Scroll to the bottom to bring zzz.txt into view
		await page.locator('[data-cy-files-list]').evaluate((el) => el.scrollTo(0, el.scrollHeight))

		// Row must be visible and NOT in rename state
		await expect(filesListPage.getRowForFile('zzz.txt')).toBeVisible()
		await expect(filesListPage.getRenameInputForFile('zzz.txt')).not.toBeVisible()
	})

	test('shows extension-change warning — keep new extension', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('file.txt')
		await input.fill('file.md')
		await input.press('Enter')

		await page.getByRole('dialog', { name: 'Change file extension' })
			.getByRole('button', { name: 'Use .md' })
			.click()

		await expect(filesListPage.getRowForFile('file.md')).toBeVisible()
	})

	test('shows extension-change warning — keep old extension', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('file.txt')
		await input.fill('document.md')
		await input.press('Enter')

		await page.getByRole('dialog', { name: 'Change file extension' })
			.getByRole('button', { name: 'Keep .txt' })
			.click()

		await expect(filesListPage.getRowForFile('document.txt')).toBeVisible()
	})

	test('shows extension-removal warning', async ({ page, filesListPage }) => {
		await expect(filesListPage.getRowForFile('file.txt')).toBeVisible()

		await filesListPage.triggerActionForFile('file.txt', 'rename')
		const input = filesListPage.getRenameInputForFile('file.txt')
		await input.fill('file')
		await input.press('Enter')

		const dialog = page.getByRole('dialog', { name: 'Change file extension' })
		await expect(dialog.getByRole('button', { name: 'Keep .txt' })).toBeVisible()
		await dialog.getByRole('button', { name: 'Remove extension' }).click()

		await expect(filesListPage.getRowForFile('file')).toBeVisible()
		await expect(filesListPage.getRowForFile('file.txt')).toHaveCount(0)
	})

	test('does not show extension warning when renaming a folder with a dot', async ({ page, user, filesListPage }) => {
		await mkdir(page.request, user, '/folder.2024')
		await filesListPage.open()

		await expect(filesListPage.getRowForFile('folder.2024')).toBeVisible()

		await filesListPage.triggerActionForFile('folder.2024', 'rename')
		const input = filesListPage.getRenameInputForFolder('folder.2024')
		await expect(input).toBeVisible()
		await input.fill('folder.2025')
		await expect(input).toHaveValidationMessage('')
		await input.press('Enter')

		await expect(page.locator('[role="dialog"]')).toHaveCount(0)
		await expect(filesListPage.getRowForFile('folder.2025')).toBeVisible()
	})
})
