/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it, vi } from 'vitest'
import { logger } from './logger.ts'

describe('files_trashbin: logger', () => {
	// Rest of the logger is not under our responsibility but nextcloud-logger
	it('has correct app name set up', () => {
		const consoleSpy = vi.spyOn(globalThis.console, 'error').mockImplementationOnce(() => {})

		logger.error('<message>')
		expect(consoleSpy).toBeCalledTimes(1)
		expect(consoleSpy.mock.calls[0][0]).toContain('<message>')
		expect(consoleSpy.mock.calls[0][0]).toContain('files_trashbin')
		expect(consoleSpy.mock.calls[0][1].app).toBe('files_trashbin')
	})
})
