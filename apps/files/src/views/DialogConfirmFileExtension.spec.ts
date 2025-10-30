/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { cleanup, fireEvent, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DialogConfirmFileExtension from './DialogConfirmFileExtension.vue'
import { useUserConfigStore } from '../store/userconfig.ts'

describe('DialogConfirmFileExtension', () => {
	beforeEach(cleanup)

	it('renders with both extensions', async () => {
		const component = render(DialogConfirmFileExtension, {
			props: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByRole('dialog', { name: 'Change file extension' })).resolves.not.toThrow()
		expect((component.getByRole('checkbox', { name: /Do not show this dialog again/i }) as HTMLInputElement).checked).toBe(false)
		await expect(component.findByRole('button', { name: 'Keep .old' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Use .new' })).resolves.not.toThrow()
	})

	it('renders without old extension', async () => {
		const component = render(DialogConfirmFileExtension, {
			props: {
				newExtension: '.new',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByRole('dialog', { name: 'Change file extension' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Keep without extension' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Use .new' })).resolves.not.toThrow()
	})

	it('renders without new extension', async () => {
		const component = render(DialogConfirmFileExtension, {
			props: {
				oldExtension: '.old',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByRole('dialog', { name: 'Change file extension' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Keep .old' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Remove extension' })).resolves.not.toThrow()
	})

	it('emits correct value on keep old', async () => {
		const onclose = vi.fn()
		const component = render(DialogConfirmFileExtension, {
			props: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			listeners: {
				close: onclose,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await fireEvent.click(component.getByRole('button', { name: 'Keep .old' }))
		expect(onclose).toHaveBeenCalledOnce()
		expect(onclose).toHaveBeenCalledWith(false)
	})

	it('emits correct value on use new', async () => {
		const onclose = vi.fn()
		const component = render(DialogConfirmFileExtension, {
			props: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			listeners: {
				close: onclose,
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await fireEvent.click(component.getByRole('button', { name: 'Use .new' }))
		expect(onclose).toHaveBeenCalledOnce()
		expect(onclose).toHaveBeenCalledWith(true)
	})

	it('updates user config when checking the checkbox', async () => {
		const pinia = createTestingPinia({
			createSpy: vi.fn,
		})

		const component = render(DialogConfirmFileExtension, {
			props: {
				oldExtension: '.old',
				newExtension: '.new',
			},
			global: {
				plugins: [pinia],
			},
		})

		await fireEvent.click(component.getByRole('checkbox', { name: /Do not show this dialog again/i }))
		const store = useUserConfigStore()
		expect(store.update).toHaveBeenCalledOnce()
		expect(store.update).toHaveBeenCalledWith('show_dialog_file_extension', false)
	})
})
