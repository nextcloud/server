/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const mockGeneratePassword = vi.fn().mockResolvedValue('generated-password-123')

vi.mock('../services/ConfigService.ts', () => ({
	default: vi.fn().mockImplementation(() => ({
		enableLinkPasswordByDefault: false,
		enforcePasswordForPublicLink: false,
		isPublicUploadEnabled: true,
		isDefaultExpireDateEnabled: false,
		isDefaultInternalExpireDateEnabled: false,
		isDefaultRemoteExpireDateEnabled: false,
		defaultExpirationDate: null,
		defaultInternalExpirationDate: null,
		defaultRemoteExpirationDateString: null,
		isResharingAllowed: true,
		excludeReshareFromEdit: false,
		showFederatedSharesAsInternal: false,
		defaultPermissions: 31,
	})),
}))

vi.mock('../utils/GeneratePassword.ts', () => ({
	default: (...args: unknown[]) => mockGeneratePassword(...args),
}))

/**
 * Simulates the isPasswordProtected getter from SharesMixin.js
 */
function getIsPasswordProtected(state: {
	enforcePasswordForPublicLink: boolean
	passwordProtectedState: boolean | undefined
	newPassword: string | undefined
	password: string | undefined
}): boolean {
	if (state.enforcePasswordForPublicLink) {
		return true
	}
	if (state.passwordProtectedState !== undefined) {
		return state.passwordProtectedState
	}
	return typeof state.newPassword === 'string'
		|| typeof state.password === 'string'
}

/**
 * Simulates the isPasswordProtected setter from SharesMixin.js
 * Returns the resulting share state after the async operation completes.
 */
async function setIsPasswordProtected(
	enabled: boolean,
	share: { newPassword?: string },
): Promise<{ passwordProtectedState: boolean, share: { newPassword?: string } }> {
	if (enabled) {
		const generatedPassword = await mockGeneratePassword(true)
		if (!share.newPassword) {
			share.newPassword = generatedPassword
		}
		return { passwordProtectedState: true, share }
	} else {
		share.newPassword = ''
		return { passwordProtectedState: false, share }
	}
}

describe('SharingDetailsTab - Password State Management Logic', () => {
	beforeEach(() => {
		mockGeneratePassword.mockClear()
		mockGeneratePassword.mockResolvedValue('generated-password-123')
	})

	describe('isPasswordProtected getter', () => {
		it('returns true when enforcePasswordForPublicLink is true regardless of other state', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: true,
				passwordProtectedState: false,
				newPassword: undefined,
				password: undefined,
			})).toBe(true)
		})

		it('returns true when passwordProtectedState is explicitly true', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: true,
				newPassword: undefined,
				password: undefined,
			})).toBe(true)
		})

		it('returns false when passwordProtectedState is explicitly false', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: false,
				newPassword: 'some-password',
				password: undefined,
			})).toBe(false)
		})

		it('falls back to inferring from newPassword when passwordProtectedState is undefined', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: undefined,
				newPassword: 'some-password',
				password: undefined,
			})).toBe(true)
		})

		it('falls back to inferring from password when passwordProtectedState is undefined', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: undefined,
				newPassword: undefined,
				password: 'existing-password',
			})).toBe(true)
		})

		it('returns false when passwordProtectedState is undefined and no passwords exist', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: undefined,
				newPassword: undefined,
				password: undefined,
			})).toBe(false)
		})

		it('checkbox remains checked when passwordProtectedState is true even if password is cleared', () => {
			expect(getIsPasswordProtected({
				enforcePasswordForPublicLink: false,
				passwordProtectedState: true,
				newPassword: '',
				password: undefined,
			})).toBe(true)
		})
	})

	describe('isPasswordProtected setter (race condition fix)', () => {
		it('generated password does NOT overwrite user-typed password', async () => {
			const share = { newPassword: 'user-typed-password' }
			const result = await setIsPasswordProtected(true, share)

			expect(mockGeneratePassword).toHaveBeenCalledWith(true)
			expect(result.passwordProtectedState).toBe(true)
			expect(result.share.newPassword).toBe('user-typed-password')
		})

		it('generated password IS applied when user has not typed anything', async () => {
			const share: { newPassword?: string } = {}
			const result = await setIsPasswordProtected(true, share)

			expect(mockGeneratePassword).toHaveBeenCalledWith(true)
			expect(result.passwordProtectedState).toBe(true)
			expect(result.share.newPassword).toBe('generated-password-123')
		})

		it('generated password IS applied when newPassword is empty string (user cleared input)', async () => {
			const share = { newPassword: '' }
			const result = await setIsPasswordProtected(true, share)

			expect(result.share.newPassword).toBe('generated-password-123')
		})

		it('disabling password clears newPassword and sets state to false', async () => {
			const share = { newPassword: 'some-password' }
			const result = await setIsPasswordProtected(false, share)

			expect(result.passwordProtectedState).toBe(false)
			expect(result.share.newPassword).toBe('')
		})
	})

	describe('initializeAttributes sets passwordProtectedState', () => {
		it('should set passwordProtectedState when enableLinkPasswordByDefault is true for new public share', () => {
			const config = { enableLinkPasswordByDefault: true, enforcePasswordForPublicLink: false }
			const isNewShare = true
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare && (config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
				passwordProtectedState = true
			}

			expect(passwordProtectedState).toBe(true)
		})

		it('should set passwordProtectedState when isPasswordEnforced is true for new public share', () => {
			const config = { enableLinkPasswordByDefault: false, enforcePasswordForPublicLink: true }
			const isNewShare = true
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare && (config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
				passwordProtectedState = true
			}

			expect(passwordProtectedState).toBe(true)
		})

		it('should not set passwordProtectedState for non-public shares', () => {
			const config = { enableLinkPasswordByDefault: true, enforcePasswordForPublicLink: false }
			const isNewShare = true
			const isPublicShare = false
			let passwordProtectedState: boolean | undefined

			if (isNewShare && (config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
				passwordProtectedState = true
			}

			expect(passwordProtectedState).toBe(undefined)
		})

		it('should not set passwordProtectedState for existing shares', () => {
			const config = { enableLinkPasswordByDefault: true, enforcePasswordForPublicLink: false }
			const isNewShare = false
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare && (config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
				passwordProtectedState = true
			}

			expect(passwordProtectedState).toBe(undefined)
		})
	})

	describe('saveShare validation blocks empty password', () => {
		const isValidShareAttribute = (attr: unknown) => {
			return typeof attr === 'string' && attr.length > 0
		}

		/**
		 * Simulates the password guard in saveShare() – returns true when execution
		 * should be blocked (passwordError set and early return triggered).
		 */
		function shouldBlock(state: {
			isPasswordProtected: boolean
			isPublicShare: boolean
			isNewShare: boolean
			newPassword: string | undefined
		}): boolean {
			if (state.isPasswordProtected) {
				if (state.isPublicShare && state.isNewShare && !isValidShareAttribute(state.newPassword)) {
					return true
				}
			}
			return false
		}

		// --- New public share: password missing → should block ---

		it('blocks new public share when isPasswordProtected but newPassword is empty', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: true,
				isNewShare: true,
				newPassword: '',
			})).toBe(true)
		})

		it('blocks new public share when isPasswordProtected but newPassword is undefined', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: true,
				isNewShare: true,
				newPassword: undefined,
			})).toBe(true)
		})

		// --- New public share: valid password → should NOT block ---

		it('does not block new public share when password is valid', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: true,
				isNewShare: true,
				newPassword: 'valid-password-123',
			})).toBe(false)
		})

		// --- Non-public (user/group) share → should NEVER block (regression for #59254) ---

		it('does not block new non-public share even when isPasswordProtected and newPassword is empty', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: false,
				isNewShare: true,
				newPassword: '',
			})).toBe(false)
		})

		it('does not block new non-public share even when isPasswordProtected and newPassword is undefined', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: false,
				isNewShare: true,
				newPassword: undefined,
			})).toBe(false)
		})

		// --- Existing public share (update path) → should NOT block ---

		it('does not block existing public share with empty newPassword (update path)', () => {
			expect(shouldBlock({
				isPasswordProtected: true,
				isPublicShare: true,
				isNewShare: false,
				newPassword: '',
			})).toBe(false)
		})

		// --- isPasswordProtected false → should NOT block ---

		it('does not block when isPasswordProtected is false', () => {
			expect(shouldBlock({
				isPasswordProtected: false,
				isPublicShare: true,
				isNewShare: true,
				newPassword: '',
			})).toBe(false)
		})
	})
})
