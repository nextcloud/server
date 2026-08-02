/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import NewNodeDialog from './NewNodeDialog.vue'

describe('NewNodeDialog', () => {
	afterEach(cleanup)

	it('shows a single inline error for a duplicate name without reporting native validity', async () => {
		const component = render(NewNodeDialog, {
			props: {
				otherNames: ['existing.txt'],
			},
		})
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement
		const reportValidity = vi.spyOn(input, 'reportValidity')
		const setCustomValidity = vi.spyOn(input, 'setCustomValidity')

		await fireEvent.update(input, 'existing.txt')

		expect(component.getAllByText('This name is already in use.')).toHaveLength(1)
		expect(component.getByRole('button', { name: 'Create' })).toBeDisabled()
		expect(setCustomValidity).toHaveBeenLastCalledWith('This name is already in use.')
		expect(input.validity.valid).toBe(false)
		expect(input.validationMessage).toBe('This name is already in use.')
		expect(reportValidity).not.toHaveBeenCalled()
	})

	it('clears inline and native validity when the name becomes unique', async () => {
		const component = render(NewNodeDialog, {
			props: {
				otherNames: ['existing.txt'],
			},
		})
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement

		await fireEvent.update(input, 'existing.txt')
		expect(input.validationMessage).toBe('This name is already in use.')

		await fireEvent.update(input, 'unique.txt')

		expect(component.queryByText('This name is already in use.')).not.toBeInTheDocument()
		expect(input.validity.valid).toBe(true)
		expect(input.validationMessage).toBe('')
		expect(component.getByRole('button', { name: 'Create' })).toBeEnabled()
	})

	it('shows other filename errors inline without reporting native validity', async () => {
		const component = render(NewNodeDialog)
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement
		const reportValidity = vi.spyOn(input, 'reportValidity')

		await fireEvent.update(input, '')

		expect(component.getAllByText('Filename must not be empty.')).toHaveLength(1)
		expect(component.getByRole('button', { name: 'Create' })).toBeDisabled()
		expect(input.validity.valid).toBe(false)
		expect(input.validationMessage).toBe('Filename must not be empty.')
		expect(reportValidity).not.toHaveBeenCalled()
	})
})
