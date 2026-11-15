/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator, Page } from '@playwright/test'

import { expect, test } from '../../support/fixtures/files-page.ts'
import { uploadContent } from '../../support/utils/dav.ts'

type Sample = { t: number, poppers: { id: string, placement: string | null, cls: string, opacity: string, x: number, y: number, h: number }[] }

/**
 * Record every popper in the app content on each animation frame while `action` runs
 */
async function recordPoppers(page: Page, action: () => Promise<void>, durationMs = 1000): Promise<Sample[]> {
	await page.evaluate(() => {
		const w = window as unknown as { __samples: Sample[], __stop: boolean }
		w.__samples = []
		w.__stop = false
		const start = performance.now()
		const tick = () => {
			w.__samples.push({
				t: Math.round(performance.now() - start),
				poppers: [...document.querySelectorAll<HTMLElement>('main.app-content .v-popper__popper')]
					.filter((el) => el.offsetParent !== null || getComputedStyle(el).visibility !== 'hidden')
					.map((el) => {
						const rect = el.getBoundingClientRect()
						return {
							id: el.id,
							placement: el.getAttribute('data-popper-placement'),
							cls: el.className,
							opacity: getComputedStyle(el).opacity,
							x: Math.round(rect.x),
							y: Math.round(rect.y),
							h: Math.round(rect.height),
						}
					})
					.filter((p) => p.h > 0 && p.opacity !== '0'),
			})
			if (!w.__stop) {
				requestAnimationFrame(tick)
			}
		}
		requestAnimationFrame(tick)
	})
	// record the state before the action
	await page.waitForFunction(() => (window as unknown as { __samples: Sample[] }).__samples.length > 0)
	await action()
	await page.waitForTimeout(durationMs)
	return page.evaluate(() => {
		const w = window as unknown as { __samples: Sample[], __stop: boolean }
		w.__stop = true
		return w.__samples
	})
}

/**
 * Collapse consecutive identical frames to keep the log readable
 */
function summarize(samples: Sample[]): string {
	const lines: string[] = []
	let last = ''
	for (const sample of samples) {
		const state = sample.poppers.map((p) => `${p.id}[${p.placement}] @${p.x},${p.y} h=${p.h} o=${p.opacity}`).join(' | ') || '(none)'
		if (state !== last) {
			lines.push(`${String(sample.t).padStart(5)}ms ${state}`)
			last = state
		}
	}
	return lines.join('\n')
}

/**
 * A visible menu must never move, neither a closing nor an opening one
 */
function expectNoMovingPopper(samples: Sample[], log: string) {
	const positionsById = new Map<string, Set<string>>()
	for (const popper of samples.flatMap((s) => s.poppers)) {
		positionsById.set(popper.id, (positionsById.get(popper.id) ?? new Set()).add(`${popper.x},${popper.y}`))
	}
	expect(positionsById.size, log).toBe(2)
	for (const positions of positionsById.values()) {
		expect(positions.size, log).toBe(1)
	}
}

async function rightClick(row: Locator) {
	const box = (await row.boundingBox())!
	await row.page().mouse.click(box.x + box.width / 3, box.y + box.height / 2, { button: 'right' })
}

test.describe('Files: right click actions menu', () => {
	test.beforeEach(async ({ user, page, filesListPage }) => {
		for (let i = 1; i <= 15; i++) {
			await uploadContent(page.request, user, Buffer.from('hello'), 'text/plain', `/file-${String(i).padStart(2, '0')}.txt`)
		}
		await filesListPage.open()
		await expect(filesListPage.getRowForFile('file-15.txt')).toBeVisible()
	})

	test('menu stays at one position when opened', async ({ page, filesListPage }) => {
		const samples = await recordPoppers(page, () => rightClick(filesListPage.getRowForFile('file-02.txt')))
		const log = summarize(samples)
		await test.info().attach('first-open', { body: log, contentType: 'text/plain' })

		const positions = new Set(samples.flatMap((s) => s.poppers.map((p) => `${p.x},${p.y}`)))
		expect(positions.size, log).toBe(1)
	})

	test('no menu moves when switching rows', async ({ page, filesListPage }) => {
		await rightClick(filesListPage.getRowForFile('file-02.txt'))
		await page.waitForTimeout(500)

		const samples = await recordPoppers(page, () => rightClick(filesListPage.getRowForFile('file-06.txt')))
		const log = summarize(samples)
		await test.info().attach('switch-rows', { body: log, contentType: 'text/plain' })

		expectNoMovingPopper(samples, log)
	})

	test('menu opened by its trigger after a right click opens under the trigger', async ({ page, filesListPage }) => {
		await rightClick(filesListPage.getRowForFile('file-02.txt'))
		await page.waitForTimeout(500)

		const row = filesListPage.getRowForFile('file-06.txt')
		await row.hover()
		const trigger = row.getByRole('button', { name: 'Actions' })
		const triggerBox = (await trigger.boundingBox())!
		const samples = await recordPoppers(page, () => trigger.click({ force: true }))
		const log = summarize(samples)
		await test.info().attach('trigger-after-right-click', { body: log, contentType: 'text/plain' })

		expectNoMovingPopper(samples, log)
		const final = samples.at(-1)!.poppers
		expect(final, log).toHaveLength(1)
		// placed by floating-vue right under the trigger, not at the right click position
		expect(Math.abs(final[0].y - (triggerBox.y + triggerBox.height)), log).toBeLessThan(20)
	})

	test('menu near the bottom opens next to the cursor', async ({ page, filesListPage }) => {
		await page.setViewportSize({ width: 1280, height: 900 })
		const row = filesListPage.getRowForFile('file-15.txt')
		await expect(row).toBeInViewport()
		const box = (await row.boundingBox())!
		const samples = await recordPoppers(page, () => rightClick(row))
		const log = summarize(samples)
		await test.info().attach('bottom-open', { body: log, contentType: 'text/plain' })

		const final = samples.at(-1)!.poppers[0]
		const cursorY = box.y + box.height / 2
		// the menu either starts at the cursor or ends at the cursor
		expect(Math.min(Math.abs(final.y - cursorY), Math.abs(final.y + final.h - cursorY)), log).toBeLessThan(50)
	})
})
