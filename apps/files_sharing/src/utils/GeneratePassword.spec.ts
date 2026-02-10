/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const axiosGet = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: { get: axiosGet } }))

const getCapabilities = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/capabilities', () => ({ getCapabilities }))

vi.mock('@nextcloud/dialogs', () => ({
	showError: vi.fn(),
	showSuccess: vi.fn(),
}))

describe('GeneratePassword', () => {
	beforeEach(() => {
		vi.resetAllMocks()
		vi.resetModules()
	})

	it('should pass context=sharing to the API', async () => {
		getCapabilities.mockReturnValue({
			password_policy: {
				api: { generate: 'https://example.com/api/generate' },
			},
		})
		axiosGet.mockResolvedValue({
			data: { ocs: { data: { password: 'generated-password' } } },
		})

		const { default: generatePassword } = await import('./GeneratePassword.ts')
		const password = await generatePassword()

		expect(axiosGet).toHaveBeenCalledWith(
			'https://example.com/api/generate',
			{ params: { context: 'sharing' } },
		)
		expect(password).toBe('generated-password')
	})

	it('should use sharing policy minLength in fallback', async () => {
		getCapabilities.mockReturnValue({
			password_policy: {
				policies: {
					sharing: { minLength: 15, enforceSpecialCharacters: false },
				},
			},
		})

		const { default: generatePassword } = await import('./GeneratePassword.ts')
		const password = await generatePassword()

		expect(password.length).toBeGreaterThanOrEqual(15)
	})

	it('should include special characters when policy requires it', async () => {
		getCapabilities.mockReturnValue({
			password_policy: {
				policies: {
					sharing: { minLength: 10, enforceSpecialCharacters: true },
				},
			},
		})

		const { default: generatePassword } = await import('./GeneratePassword.ts')
		const password = await generatePassword()

		expect(password).toMatch(/[!@#$%^&*]/)
	})

	it('should fallback to default 10 chars when no policy', async () => {
		getCapabilities.mockReturnValue({})

		const { default: generatePassword } = await import('./GeneratePassword.ts')
		const password = await generatePassword()

		expect(password.length).toBeGreaterThanOrEqual(10)
	})
})
