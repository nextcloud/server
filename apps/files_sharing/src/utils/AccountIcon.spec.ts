/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { describe, expect, it, afterEach } from 'vitest'
import { generateAvatarSvg } from './AccountIcon'
describe('AccountIcon', () => {

	afterEach(() => {
		delete document.body.dataset.themes
	})

	it('should generate regular account avatar svg', () => {
		const svg = generateAvatarSvg('admin')
		expect(svg).toContain('/avatar/admin/32')
		expect(svg).not.toContain('dark')
		expect(svg).toContain('?guestFallback=true')
	})

	it('should generate guest account avatar svg', () => {
		const svg = generateAvatarSvg('admin', true)
		expect(svg).toContain('/avatar/guest/admin/32')
		expect(svg).not.toContain('dark')
		expect(svg).not.toContain('?guestFallback=true')
	})

	it('should generate dark mode account avatar svg', () => {
		document.body.dataset.themes = 'dark'
		const svg = generateAvatarSvg('admin')
		expect(svg).toContain('/avatar/admin/32/dark')
		expect(svg).toContain('?guestFallback=true')
	})

	it('should generate dark mode guest account avatar svg', () => {
		document.body.dataset.themes = 'dark'
		const svg = generateAvatarSvg('admin', true)
		expect(svg).toContain('/avatar/guest/admin/32/dark')
		expect(svg).not.toContain('?guestFallback=true')
	})
})
