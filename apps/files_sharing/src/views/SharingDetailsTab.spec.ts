/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import SharingDetailsTab from './SharingDetailsTab.vue'

type Ctx = ReturnType<typeof buildContext>

function buildContext(overrides: Partial<Ctx> = {}) {
	const ctx = {
		passwordError: false,
		creating: false,
		writeNoteToRecipientIsChecked: true,
		limitAllowedFileTypesIsChecked: false,
		allowedFileTypes: '',
		advancedSectionAccordionExpanded: false,
		sharingPermission: '31',
		setCustomPermissions: false,

		fileInfo: { path: '/foo', name: 'foo' },
		share: {
			_share: { id: null },
			id: null,
			permissions: 31,
			type: 3,
			shareWith: '',
			attributes: null as Array<{ scope: string, key: string, value: unknown }> | null,
			note: '',
			newPassword: undefined as string | undefined,
		},

		config: { allowCustomTokens: false },
		bundledPermissions: { ALL: 31, ALL_FILE: 15 },
		hasUnsavedPassword: false,
		hasExpirationDate: false,
		isExpiryDateEnforced: false,
		isFolder: false,
		isNewShare: true,
		isPasswordProtected: false,
		isPublicShare: true,
		showAllowedFileTypes: false,

		isValidShareAttribute: (v: unknown) => typeof v === 'string' && v.length > 0,
		getShareAttribute: SharingDetailsTab.methods.getShareAttribute,
		setShareAttribute: SharingDetailsTab.methods.setShareAttribute,
		removeShareAttribute: SharingDetailsTab.methods.removeShareAttribute,
		updateAtomicPermissions: vi.fn(),
		addShare: vi.fn().mockResolvedValue({ id: 42, _share: { id: 42 } }),
		queueUpdate: vi.fn().mockResolvedValue(undefined),
		getNode: vi.fn().mockResolvedValue(undefined),

		$emit: vi.fn(),
		$refs: { externalShareActions: [] as unknown[], externalLinkActions: [] as unknown[] },

		...overrides,
	}
	return ctx
}

async function callSaveShare(ctx: Ctx) {
	return SharingDetailsTab.methods.saveShare.call(ctx)
}

describe('SharingDetailsTab.saveShare — password guard', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	describe('new public share with invalid password', () => {
		it('blocks when newPassword is empty string', async () => {
			const ctx = buildContext({
				isPublicShare: true,
				isNewShare: true,
				isPasswordProtected: true,
				hasUnsavedPassword: true,
				share: { ...buildContext().share, newPassword: '' },
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(true)
			expect(ctx.addShare).not.toHaveBeenCalled()
			expect(ctx.queueUpdate).not.toHaveBeenCalled()
		})

		it('blocks when newPassword is undefined', async () => {
			const ctx = buildContext({
				isPublicShare: true,
				isNewShare: true,
				isPasswordProtected: true,
				hasUnsavedPassword: false,
				share: { ...buildContext().share, newPassword: undefined },
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(true)
			expect(ctx.addShare).not.toHaveBeenCalled()
			expect(ctx.queueUpdate).not.toHaveBeenCalled()
		})
	})

	describe('new public share with valid password', () => {
		it('creates the share with the password in the payload', async () => {
			const ctx = buildContext({
				isPublicShare: true,
				isNewShare: true,
				isPasswordProtected: true,
				hasUnsavedPassword: true,
				share: { ...buildContext().share, newPassword: 'myPass123' },
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(false)
			expect(ctx.addShare).toHaveBeenCalledTimes(1)
			expect(ctx.addShare).toHaveBeenCalledWith(expect.objectContaining({ password: 'myPass123' }))
		})
	})

	describe('new non-public share (regression guard for #59254)', () => {
		it('never blocks on the password guard, even when isPasswordProtected leaks true', async () => {
			const ctx = buildContext({
				isPublicShare: false,
				isNewShare: true,
				isPasswordProtected: true,
				hasUnsavedPassword: false,
				share: { ...buildContext().share, type: 0, newPassword: undefined },
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(false)
			expect(ctx.addShare).toHaveBeenCalledTimes(1)
		})
	})

	describe('existing public share (update path)', () => {
		it('does not run the new-share password guard', async () => {
			const ctx = buildContext({
				isPublicShare: true,
				isNewShare: false,
				isPasswordProtected: true,
				hasUnsavedPassword: false,
				share: { ...buildContext().share, id: 42, _share: { id: 42 }, newPassword: undefined },
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(false)
			expect(ctx.addShare).not.toHaveBeenCalled()
			expect(ctx.queueUpdate).toHaveBeenCalledTimes(1)
		})
	})

	describe('password checkbox unchecked', () => {
		it('omits password from the create payload', async () => {
			const ctx = buildContext({
				isPublicShare: true,
				isNewShare: true,
				isPasswordProtected: false,
				hasUnsavedPassword: false,
			})

			await callSaveShare(ctx)

			expect(ctx.passwordError).toBe(false)
			expect(ctx.addShare).toHaveBeenCalledTimes(1)
			const payload = ctx.addShare.mock.calls[0][0] as Record<string, unknown>
			expect(payload).not.toHaveProperty('password')
		})
	})
})

describe('SharingDetailsTab — allowed file types', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('saves the comma separated list as share attribute', async () => {
		const ctx = buildContext({
			showAllowedFileTypes: true,
			limitAllowedFileTypesIsChecked: true,
			allowedFileTypes: ' .pdf,image/* , ,video/*',
		})

		await callSaveShare(ctx)

		expect(ctx.addShare).toHaveBeenCalledWith(expect.objectContaining({
			attributes: [{ scope: 'upload', key: 'allowed_types', value: ['.pdf', 'image/*', 'video/*'] }],
		}))
	})

	it('adds the leading dot to extensions entered without it', async () => {
		const ctx = buildContext({
			showAllowedFileTypes: true,
			limitAllowedFileTypesIsChecked: true,
			allowedFileTypes: 'pdf, image/*',
		})

		await callSaveShare(ctx)

		expect(ctx.addShare).toHaveBeenCalledWith(expect.objectContaining({
			attributes: [{ scope: 'upload', key: 'allowed_types', value: ['.pdf', 'image/*'] }],
		}))
	})

	it.each([
		['the limit is disabled', { showAllowedFileTypes: true, limitAllowedFileTypesIsChecked: false, allowedFileTypes: '.pdf' }],
		['the field is empty', { showAllowedFileTypes: true, limitAllowedFileTypesIsChecked: true, allowedFileTypes: ' , ' }],
		['the share does not allow uploads', { showAllowedFileTypes: false, limitAllowedFileTypesIsChecked: true, allowedFileTypes: '.pdf' }],
	])('removes the share attribute when %s', async (_, overrides) => {
		const ctx = buildContext({
			...overrides,
			share: {
				...buildContext().share,
				attributes: [
					{ scope: 'permissions', key: 'download', value: true },
					{ scope: 'upload', key: 'allowed_types', value: ['.pdf'] },
				],
			},
		})

		await callSaveShare(ctx)

		expect(ctx.share.attributes).toEqual([{ scope: 'permissions', key: 'download', value: true }])
	})

	it('restores the limit when editing an existing share', async () => {
		const ctx = buildContext({
			isNewShare: false,
			share: {
				...buildContext().share,
				attributes: [{ scope: 'upload', key: 'allowed_types', value: ['.pdf', 'image/*'] }],
			},
		})

		await SharingDetailsTab.methods.initializeAttributes.call(ctx)

		expect(ctx.limitAllowedFileTypesIsChecked).toBe(true)
		expect(ctx.allowedFileTypes).toBe('.pdf, image/*')
		expect(ctx.advancedSectionAccordionExpanded).toBe(true)
	})
})

describe('SharingDetailsTab.allowsUpload', () => {
	it.each([
		['view only', '1', false, false],
		['upload and editing', '31', false, true],
		['file request', '4', false, true],
		['custom permissions without create', 'custom', false, false],
		['custom permissions with create', 'custom', true, true],
	])('%s', (_, sharingPermission, canCreate, expected) => {
		expect(SharingDetailsTab.computed.allowsUpload.call({ sharingPermission, canCreate } as never)).toBe(expected)
	})
})

describe('SharingDetailsTab.showAllowedFileTypes', () => {
	it.each([
		['public folder share with uploads', true, true, true, true],
		['public folder share without uploads', true, true, false, false],
		['public file share', true, false, true, false],
		['internal folder share', false, true, true, false],
	])('%s', (_, isPublicShare, isFolder, allowsUpload, expected) => {
		expect(SharingDetailsTab.computed.showAllowedFileTypes.call({ isPublicShare, isFolder, allowsUpload } as never)).toBe(expected)
	})
})

describe('SharingDetailsTab.allPermissions — capped by what the resharer holds', () => {
	/**
	 * @param sharePermissions what the current user received on the node
	 * @param isFolder whether the shared node is a folder
	 */
	function permissionsFor(sharePermissions: string | undefined, isFolder = true) {
		const ctx = {
			isFolder,
			fileInfo: { sharePermissions },
			bundledPermissions: { ALL: 31, ALL_FILE: 15 },
		} as never
		const grantable = SharingDetailsTab.computed.grantablePermissions.call(ctx)
		return SharingDetailsTab.computed.allPermissions.call({ ...(ctx as object), grantablePermissions: grantable })
	}

	it('keeps every bit when the user owns the node', () => {
		expect(permissionsFor('31')).toBe('31')
	})

	it('drops delete when the incoming share has no delete permission', () => {
		expect(permissionsFor('23')).toBe('23')
	})

	it('drops create and delete on a read-only reshare', () => {
		expect(permissionsFor('17')).toBe('17')
	})

	it('caps file shares against ALL_FILE', () => {
		expect(permissionsFor('31', false)).toBe('15')
	})

	it('falls back to full permissions when the DAV property is missing', () => {
		expect(permissionsFor(undefined)).toBe('31')
	})
})

describe('SharingDetailsTab.saveShare — notifying the share list', () => {
	beforeEach(() => {
		vi.clearAllMocks()
	})

	it('emits the share created on the server', async () => {
		const created = { id: 42, _share: { id: 42 } }
		const ctx = buildContext({
			isPublicShare: false,
			addShare: vi.fn().mockResolvedValue(created),
		})

		await callSaveShare(ctx)

		expect(ctx.$emit).toHaveBeenCalledWith('add:share', created)
		expect(ctx.share._share.id).toBe(42)
	})

	it('saves the legacy external actions through their rendered component', async () => {
		const onSave = vi.fn().mockResolvedValue(undefined)
		const ctx = buildContext({
			isNewShare: false,
			share: { ...buildContext().share, id: 42, _share: { id: 42 } },
			$refs: {
				externalShareActions: [],
				externalLinkActions: [{ $refs: { component: { onSave } } }, { $refs: {} }],
			},
		})

		await callSaveShare(ctx)

		expect(onSave).toHaveBeenCalledOnce()
		expect(ctx.$emit).toHaveBeenCalledWith('close-sharing-details')
	})
})

describe('SharingDetailsTab.passwordExpirationTime', () => {
	beforeEach(() => {
		vi.useFakeTimers({ now: new Date('2026-01-01T00:00:00Z') })
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	/**
	 * @param passwordExpirationTime The password expiration time of the share
	 */
	function expirationFor(passwordExpirationTime: string | null) {
		const ctx = {
			share: { passwordExpirationTime },
			isValidShareAttribute: (v: unknown) => typeof v === 'string' && v.length > 0,
		}
		return SharingDetailsTab.computed.passwordExpirationTime.call(ctx as never)
	}

	it('shows the time left until the password expires', () => {
		expect(expirationFor('2026-01-03T00:00:00Z')).toBe('in 2 days')
	})

	it('reports an expired password', () => {
		expect(expirationFor('2025-12-31T00:00:00Z')).toBe(false)
	})

	it('reports no expiration when the password does not expire', () => {
		expect(expirationFor(null)).toBe(null)
	})
})
