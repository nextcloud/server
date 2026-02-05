/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createTestingPinia } from '@pinia/testing'
import { cleanup, fireEvent, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DialogConfirmFileHidden from './DialogConfirmFileHidden.vue'
import { useUserConfigStore } from '../store/userconfig.ts'

describe('DialogConfirmFileHidden', () => {
	beforeEach(cleanup)

	it('renders', async () => {
		const component = render(DialogConfirmFileHidden, {
			props: {
				filename: '.filename.txt',
			},
			global: {
				plugins: [createTestingPinia({
					createSpy: vi.fn,
				})],
			},
		})

		await expect(component.findByRole('dialog', { name: 'Rename file to hidden' })).resolves.not.toThrow()
		expect((component.getByRole('checkbox', { name: /Do not show this dialog again/i }) as HTMLInputElement).checked).toBe(false)
		await expect(component.findByRole('button', { name: 'Cancel' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: 'Rename' })).resolves.not.toThrow()
	})

	it('emits false value on cancel', async () => {
		const onclose = vi.fn()
		const component = render(DialogConfirmFileHidden, {
			props: {
				filename: '.filename.txt',
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

		await fireEvent.click(component.getByRole('button', { name: 'Cancel' }))
		expect(onclose).toHaveBeenCalledOnce()
		expect(onclose).toHaveBeenCalledWith(false)
	})

	it('emits true on rename', async () => {
		const onclose = vi.fn()
		const component = render(DialogConfirmFileHidden, {
			props: {
				filename: '.filename.txt',
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

		await fireEvent.click(component.getByRole('button', { name: 'Rename' }))
		expect(onclose).toHaveBeenCalledOnce()
		expect(onclose).toHaveBeenCalledWith(true)
	})

	it('updates user config when checking the checkbox', async () => {
		const pinia = createTestingPinia({
			createSpy: vi.fn,
		})

		const component = render(DialogConfirmFileHidden, {
			props: {
				filename: '.filename.txt',
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
