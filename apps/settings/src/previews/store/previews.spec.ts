/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { PreviewProvider, PreviewSettings } from '../types.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { usePreviewSettingsStore } from './previews.ts'

vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('@nextcloud/password-confirmation', () => ({
	addPasswordConfirmationInterceptors: vi.fn(),
	PwdConfirmationMode: { Lax: 'lax' },
}))

const initialState = vi.hoisted(() => ({ value: {} as PreviewSettings }))
vi.mock('@nextcloud/initial-state', () => ({
	loadState: () => structuredClone(initialState.value),
}))

function provider(name: string, enabled: boolean): PreviewProvider {
	return { class: `OC\\Preview\\${name}`, name, mimetypes: '', requirement: 'none', available: true, enabled }
}

function settings(providers: PreviewProvider[]): PreviewSettings {
	return {
		configIsReadOnly: false,
		enabled: true,
		maxX: null,
		maxY: null,
		maxMemory: null,
		maxFilesizeImage: null,
		jpegQuality: null,
		webpQuality: null,
		concurrencyNew: null,
		concurrencyAll: null,
		expirationDays: null,
		providersConfigured: true,
		providers,
		dependencies: { imagick: false, ffmpeg: null, office: null, imaginary: false },
	}
}

function ocs(data: PreviewSettings) {
	return { data: { ocs: { meta: { status: 'ok', statuscode: 200, message: 'OK' }, data } } }
}

describe('store:previews', () => {
	beforeEach(() => {
		vi.restoreAllMocks()
		vi.mocked(showError).mockClear()
		vi.spyOn(axios, 'put')
		vi.spyOn(axios, 'delete')
		initialState.value = settings([provider('JPEG', true), provider('PNG', true), provider('HEIC', false)])
		setActivePinia(createPinia())
	})

	it('sends every limit and takes the returned state', async () => {
		const saved = { ...settings([]), maxX: 1024 }
		vi.mocked(axios.put).mockResolvedValue(ocs(saved))

		const store = usePreviewSettingsStore()
		await store.updateSettings({ maxX: 1024 })

		expect(axios.put).toHaveBeenCalledWith(
			expect.stringContaining('/apps/settings/api/admin/previews/settings'),
			{ enabled: true, maxX: 1024, maxY: null, maxMemory: null, maxFilesizeImage: null, jpegQuality: null, webpQuality: null, concurrencyNew: null, concurrencyAll: null, expirationDays: null },
			{ confirmPassword: 'lax' },
		)
		expect(store.settings).toEqual(saved)
	})

	it('keeps the state and reports a failed save', async () => {
		vi.mocked(axios.put).mockRejectedValue(new Error('Request failed'))

		const store = usePreviewSettingsStore()
		await store.updateSettings({ enabled: false })

		expect(store.settings.enabled).toBe(true)
		expect(showError).toHaveBeenCalledOnce()
		expect(store.saving).toBe(false)
	})

	it('appends a newly enabled provider to the try-order', async () => {
		vi.mocked(axios.put).mockResolvedValue(ocs(settings([])))

		await usePreviewSettingsStore().toggleProvider('OC\\Preview\\HEIC', true)

		expect(axios.put).toHaveBeenCalledWith(
			expect.stringContaining('/previews/providers'),
			{ providers: ['OC\\Preview\\JPEG', 'OC\\Preview\\PNG', 'OC\\Preview\\HEIC'] },
			expect.anything(),
		)
	})

	it('drops a disabled provider from the try-order', async () => {
		vi.mocked(axios.put).mockResolvedValue(ocs(settings([])))

		await usePreviewSettingsStore().toggleProvider('OC\\Preview\\JPEG', false)

		expect(vi.mocked(axios.put).mock.calls[0]![1]).toEqual({ providers: ['OC\\Preview\\PNG'] })
	})

	it('swaps a provider with its neighbour', async () => {
		vi.mocked(axios.put).mockResolvedValue(ocs(settings([])))

		await usePreviewSettingsStore().moveProvider('OC\\Preview\\PNG', -1)

		expect(vi.mocked(axios.put).mock.calls[0]![1]).toEqual({ providers: ['OC\\Preview\\PNG', 'OC\\Preview\\JPEG'] })
	})

	it('does not move a provider past either end', async () => {
		const store = usePreviewSettingsStore()
		await store.moveProvider('OC\\Preview\\JPEG', -1)
		await store.moveProvider('OC\\Preview\\PNG', 1)
		await store.moveProvider('OC\\Preview\\HEIC', -1)

		expect(axios.put).not.toHaveBeenCalled()
	})

	it('resets the providers with a delete', async () => {
		vi.mocked(axios.delete).mockResolvedValue(ocs(settings([])))

		await usePreviewSettingsStore().resetProviders()

		expect(axios.delete).toHaveBeenCalledWith(expect.stringContaining('/previews/providers'), { confirmPassword: 'lax' })
	})
})
