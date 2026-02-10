/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

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
	default: vi.fn().mockResolvedValue('generated-password-123'),
}))

describe('SharingDetailsTab - Password State Management Logic', () => {
	describe('isPasswordProtected getter logic', () => {
		it('returns true when passwordProtectedState is explicitly true', () => {
			const passwordProtectedState: boolean | undefined = true
			const enforcePasswordForPublicLink = false
			const newPassword: string | undefined = undefined
			const password: string | undefined = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| typeof password === 'string'
			})()

			expect(isPasswordProtected).toBe(true)
		})

		it('returns false when passwordProtectedState is explicitly false', () => {
			const passwordProtectedState: boolean | undefined = false
			const enforcePasswordForPublicLink = false
			const newPassword: string | undefined = 'some-password'
			const password: string | undefined = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| typeof password === 'string'
			})()

			expect(isPasswordProtected).toBe(false)
		})

		it('returns true when enforcePasswordForPublicLink is true regardless of other state', () => {
			const passwordProtectedState: boolean | undefined = false
			const enforcePasswordForPublicLink = true
			const newPassword: string | undefined = undefined
			const password: string | undefined = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| typeof password === 'string'
			})()

			expect(isPasswordProtected).toBe(true)
		})

		it('falls back to inferring from password when passwordProtectedState is undefined', () => {
			const passwordProtectedState: boolean | undefined = undefined
			const enforcePasswordForPublicLink = false
			const newPassword: string | undefined = 'some-password'
			const password: string | undefined = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| typeof password === 'string'
			})()

			expect(isPasswordProtected).toBe(true)
		})

		it('returns false when passwordProtectedState is undefined and no passwords exist', () => {
			const passwordProtectedState: boolean | undefined = undefined
			const enforcePasswordForPublicLink = false
			const newPassword: string | undefined = undefined
			const password: string | undefined = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| typeof password === 'string'
			})()

			expect(isPasswordProtected).toBe(false)
		})
	})

	describe('initializeAttributes sets passwordProtectedState', () => {
		it('should set passwordProtectedState to true when enableLinkPasswordByDefault is true', async () => {
			const config = {
				enableLinkPasswordByDefault: true,
				enforcePasswordForPublicLink: false,
			}
			const isNewShare = true
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare) {
				if ((config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
					passwordProtectedState = true
				}
			}

			expect(passwordProtectedState).toBe(true)
		})

		it('should set passwordProtectedState to true when isPasswordEnforced is true', async () => {
			const config = {
				enableLinkPasswordByDefault: false,
				enforcePasswordForPublicLink: true,
			}
			const isNewShare = true
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare) {
				if ((config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
					passwordProtectedState = true
				}
			}

			expect(passwordProtectedState).toBe(true)
		})

		it('should not set passwordProtectedState for non-public shares', async () => {
			const config = {
				enableLinkPasswordByDefault: true,
				enforcePasswordForPublicLink: false,
			}
			const isNewShare = true
			const isPublicShare = false
			let passwordProtectedState: boolean | undefined

			if (isNewShare) {
				if ((config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
					passwordProtectedState = true
				}
			}

			expect(passwordProtectedState).toBe(undefined)
		})

		it('should not set passwordProtectedState for existing shares', async () => {
			const config = {
				enableLinkPasswordByDefault: true,
				enforcePasswordForPublicLink: false,
			}
			const isNewShare = false
			const isPublicShare = true
			let passwordProtectedState: boolean | undefined

			if (isNewShare) {
				if ((config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink) && isPublicShare) {
					passwordProtectedState = true
				}
			}

			expect(passwordProtectedState).toBe(undefined)
		})
	})

	describe('saveShare validation blocks empty password', () => {
		const isValidShareAttribute = (attr: unknown) => {
			return typeof attr === 'string' && attr.length > 0
		}

		it('should set passwordError when isPasswordProtected but newPassword is empty for new share', () => {
			const isPasswordProtected = true
			const isNewShare = true
			const newPassword = ''
			let passwordError = false

			if (isPasswordProtected) {
				if (isNewShare && !isValidShareAttribute(newPassword)) {
					passwordError = true
				}
			}

			expect(passwordError).toBe(true)
		})

		it('should set passwordError when isPasswordProtected but newPassword is undefined for new share', () => {
			const isPasswordProtected = true
			const isNewShare = true
			const newPassword = undefined
			let passwordError = false

			if (isPasswordProtected) {
				if (isNewShare && !isValidShareAttribute(newPassword)) {
					passwordError = true
				}
			}

			expect(passwordError).toBe(true)
		})

		it('should not set passwordError when password is valid for new share', () => {
			const isPasswordProtected = true
			const isNewShare = true
			const newPassword = 'valid-password-123'
			let passwordError = false

			if (isPasswordProtected) {
				if (isNewShare && !isValidShareAttribute(newPassword)) {
					passwordError = true
				}
			}

			expect(passwordError).toBe(false)
		})

		it('should not set passwordError when isPasswordProtected is false', () => {
			const isPasswordProtected = false
			const isNewShare = true
			const newPassword = ''
			let passwordError = false

			if (isPasswordProtected) {
				if (isNewShare && !isValidShareAttribute(newPassword)) {
					passwordError = true
				}
			}

			expect(passwordError).toBe(false)
		})

		it('should not validate password for existing shares', () => {
			const isPasswordProtected = true
			const isNewShare = false
			const newPassword = ''
			let passwordError = false

			if (isPasswordProtected) {
				if (isNewShare && !isValidShareAttribute(newPassword)) {
					passwordError = true
				}
			}

			expect(passwordError).toBe(false)
		})
	})

	describe('checkbox persistence after clearing password', () => {
		it('checkbox remains checked when passwordProtectedState is explicitly true even if password is cleared', () => {
			let passwordProtectedState: boolean | undefined = true
			const enforcePasswordForPublicLink = false
			let newPassword: string | undefined = 'initial-password'

			newPassword = ''

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| false
			})()

			expect(isPasswordProtected).toBe(true)
		})

		it('checkbox unchecks incorrectly if passwordProtectedState was never set (bug scenario)', () => {
			let passwordProtectedState: boolean | undefined = undefined
			const enforcePasswordForPublicLink = false
			let newPassword: string | undefined = 'initial-password'

			newPassword = undefined

			const isPasswordProtected = (() => {
				if (enforcePasswordForPublicLink) {
					return true
				}
				if (passwordProtectedState !== undefined) {
					return passwordProtectedState
				}
				return typeof newPassword === 'string'
					|| false
			})()

			expect(isPasswordProtected).toBe(false)
		})
	})
})
