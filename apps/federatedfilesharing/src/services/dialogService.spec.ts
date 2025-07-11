/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { showRemoteShareDialog } from './dialogService'
import { nextTick } from 'vue'

describe('federatedfilesharing: dialog service', () => {
	it('mounts dialog', async () => {
		showRemoteShareDialog('share-name', 'user123', 'example.com')
		await nextTick()
		expect(document.querySelector('[role="dialog"]')).not.toBeNull()
		expect(document.querySelector('[role="dialog"]')!.textContent).to.contain('share-name')
		expect(document.querySelector('[role="dialog"]')!.textContent).to.contain('user123@example.com')
		expect(document.querySelector('[role="dialog"] input[type="password"]')).toBeNull()
	})

	it('shows password input', async () => {
		showRemoteShareDialog('share-name', 'user123', 'example.com', true)
		await nextTick()
		expect(document.querySelector('[role="dialog"]')).not.toBeNull()
		expect(document.querySelector('[role="dialog"] input[type="password"]')).not.toBeNull()
	})

	it('resolves if accepted', async () => {
		const promise = showRemoteShareDialog('share-name', 'user123', 'example.com')
		await nextTick()

		for (const button of document.querySelectorAll('button').values()) {
			if (button.textContent?.match(/add remote share/i)) {
				button.click()
			}
		}

		expect(await promise).toBe(undefined)
	})

	it('resolves password if accepted', async () => {
		const promise = showRemoteShareDialog('share-name', 'user123', 'example.com', true)
		await nextTick()

		for (const button of document.querySelectorAll('button').values()) {
			if (button.textContent?.match(/add remote share/i)) {
				button.click()
			}
		}

		expect(await promise).toBe('')
	})

	it('rejects if cancelled', async () => {
		const promise = showRemoteShareDialog('share-name', 'user123', 'example.com')
		await nextTick()

		for (const button of document.querySelectorAll('button').values()) {
			if (button.textContent?.match(/cancel/i)) {
				button.click()
			}
		}

		expect(async () => await promise).rejects.toThrow()
	})
})
