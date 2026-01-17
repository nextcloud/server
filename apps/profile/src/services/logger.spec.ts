/*
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test, vi } from 'vitest'
import { logger } from './logger.ts'

test('logger', () => {
	const spy = vi.spyOn(console, 'warn').mockImplementation(() => {})
	logger.warn('This is a warning message')

	expect(console.warn).toHaveBeenCalled()
	expect(spy.mock.calls[0]![0]).toContain('profile')
})
