/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vitest'
import NewNodeDialog from './NewNodeDialog.vue'

vi.mock('@nextcloud/capabilities')

describe('NewNodeDialog', () => {
	afterEach(cleanup)

	it('reports a duplicate name using the native validation only', async () => {
		const reportValidity = vi.spyOn(HTMLInputElement.prototype, 'reportValidity')
		const setCustomValidity = vi.spyOn(HTMLInputElement.prototype, 'setCustomValidity')
		const component = render(NewNodeDialog, {
			props: {
				otherNames: ['existing.txt'],
			},
		})
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement

		await fireEvent.update(input, 'existing.txt')

		expect(setCustomValidity).toHaveBeenLastCalledWith('This name is already in use.')
		expect(input.validity.valid).toBe(false)
		expect(input.validationMessage).toBe('This name is already in use.')
		expect(reportValidity).toHaveBeenCalled()
		// the message is only shown by the platform, not duplicated as helper text
		expect(component.queryByText('This name is already in use.')).not.toBeInTheDocument()
		expect(component.getByRole('button', { name: 'Create' })).toBeDisabled()
	})

	it('clears the native validity when the name becomes unique', async () => {
		const component = render(NewNodeDialog, {
			props: {
				otherNames: ['existing.txt'],
			},
		})
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement

		await fireEvent.update(input, 'existing.txt')
		expect(input.validationMessage).toBe('This name is already in use.')

		await fireEvent.update(input, 'unique.txt')

		expect(input.validity.valid).toBe(true)
		expect(input.validationMessage).toBe('')
		expect(component.getByRole('button', { name: 'Create' })).toBeEnabled()
	})

	it('reports other filename errors using the native validation only', async () => {
		const reportValidity = vi.spyOn(HTMLInputElement.prototype, 'reportValidity')
		const component = render(NewNodeDialog)
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement

		await fireEvent.update(input, '')

		expect(input.validity.valid).toBe(false)
		expect(input.validationMessage).toBe('Filename must not be empty.')
		expect(reportValidity).toHaveBeenCalled()
		expect(component.queryByText('Filename must not be empty.')).not.toBeInTheDocument()
		expect(component.getByRole('button', { name: 'Create' })).toBeDisabled()
	})

	it('does not submit a duplicate name', async () => {
		const component = render(NewNodeDialog, {
			props: {
				otherNames: ['existing.txt'],
			},
		})
		const input = component.getByRole('textbox', { name: 'Folder name' }) as HTMLInputElement
		const form = input.closest('form') as HTMLFormElement

		await fireEvent.update(input, 'existing.txt')
		// same code path as pressing enter within the form
		form.requestSubmit()

		expect(form.checkValidity()).toBe(false)
		expect(component.emitted().close).toBeUndefined()
	})
})
