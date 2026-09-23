/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { FilePickerClosed, getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { cleanup, fireEvent, render, waitFor } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import FilesAppSettingsTemplates from './FilesAppSettingsTemplates.vue'
import { templateDirectory } from '../../store/templateDirectory.ts'

vi.mock('@nextcloud/vue/components/NcAppSettingsSection', async () => {
	const { h } = await import('vue')
	return { default: { setup: (_props, { slots }) => () => h('section', slots.default?.()) } }
})
vi.mock('@nextcloud/axios', () => ({ default: { get: vi.fn(), put: vi.fn(), post: vi.fn(), delete: vi.fn() } }))
vi.mock('@nextcloud/dialogs', () => ({
	FilePickerClosed: class extends Error {},
	getFilePickerBuilder: vi.fn(),
	showError: vi.fn(),
}))

const pickNodes = vi.fn()
const builder = {
	setMultiSelect: vi.fn().mockReturnThis(),
	setMimeTypeFilter: vi.fn().mockReturnThis(),
	allowDirectories: vi.fn().mockReturnThis(),
	setCanPick: vi.fn().mockReturnThis(),
	startAt: vi.fn().mockReturnThis(),
	addButton: vi.fn().mockReturnThis(),
	build: () => ({ pickNodes }),
}

function response(path: string, available = true) {
	return { data: { ocs: { data: { template_path: path, available } } } }
}

async function mount() {
	const component = render(FilesAppSettingsTemplates)
	await waitFor(() => expect(component.getByRole('button', { name: 'Choose folder' })).not.toBeDisabled())
	return component
}

describe('Personal template folder settings', () => {
	beforeEach(() => {
		cleanup()
		vi.clearAllMocks()
		Object.assign(templateDirectory, { template_path: '', available: false })
		vi.mocked(axios.get).mockResolvedValue(response('/Templates'))
		vi.mocked(getFilePickerBuilder).mockReturnValue(builder as unknown as ReturnType<typeof getFilePickerBuilder>)
		pickNodes.mockResolvedValue([{ path: '/Documents/Templates' }])
	})

	it('loads the current directory and links to it', async () => {
		const component = await mount()
		expect(component.getByText('/Templates')).toBeVisible()
		expect(component.getByRole('button', { name: 'Open folder' })).toHaveAttribute('href', expect.stringContaining('dir=%2FTemplates'))
	})

	it('selects an existing folder without invoking initialization', async () => {
		vi.mocked(axios.put).mockResolvedValue(response('/Documents/Templates'))
		const component = await mount()
		await fireEvent.click(component.getByRole('button', { name: 'Choose folder' }))
		await waitFor(() => expect(component.getByText('/Documents/Templates')).toBeVisible())
		expect(axios.put).toHaveBeenCalledWith(expect.stringContaining('/templates/path'), { templatePath: '/Documents/Templates' })
		expect(axios.post).not.toHaveBeenCalled()
		expect(builder.setMimeTypeFilter).toHaveBeenCalledWith(['httpd/unix-directory'])
		expect(builder.startAt).toHaveBeenCalledWith('/Templates')
		expect(builder.addButton).toHaveBeenCalledWith(expect.objectContaining({ label: 'Select folder' }))
		const canPick = builder.setCanPick.mock.calls[0][0]
		expect(canPick({ permissions: 1 })).toBe(true)
		expect(canPick({ permissions: 0 })).toBe(false)
	})

	it('clears the preference without deleting files', async () => {
		vi.mocked(axios.put).mockResolvedValue(response('', false))
		const component = await mount()
		await fireEvent.click(component.getByRole('button', { name: 'Clear selection' }))
		await waitFor(() => expect(component.getByText('No folder selected')).toBeVisible())
		expect(axios.put).toHaveBeenCalledWith(expect.any(String), { templatePath: '' })
		expect(axios.delete).not.toHaveBeenCalled()
		expect(component.queryByRole('button', { name: 'Open folder' })).toBeNull()
	})

	it('keeps the old selection when saving fails', async () => {
		vi.mocked(axios.put).mockRejectedValue(new Error('Forbidden'))
		const component = await mount()
		await fireEvent.click(component.getByRole('button', { name: 'Choose folder' }))
		await waitFor(() => expect(showError).toHaveBeenCalledWith('Unable to update the template folder'))
		expect(component.getByText('/Templates')).toBeVisible()
	})

	it('does not save or show an error when the picker is cancelled', async () => {
		pickNodes.mockRejectedValue(new FilePickerClosed())
		const component = await mount()
		await fireEvent.click(component.getByRole('button', { name: 'Choose folder' }))
		await waitFor(() => expect(pickNodes).toHaveBeenCalled())
		expect(axios.put).not.toHaveBeenCalled()
		expect(showError).not.toHaveBeenCalled()
	})

	it('reports picker failures', async () => {
		pickNodes.mockRejectedValue(new Error('Picker failed'))
		const component = await mount()
		await fireEvent.click(component.getByRole('button', { name: 'Choose folder' }))
		await waitFor(() => expect(showError).toHaveBeenCalledWith('Unable to choose a template folder'))
	})

	it('shows unavailable paths and starts the picker at the root', async () => {
		vi.mocked(axios.get).mockResolvedValue(response('/Missing', false))
		pickNodes.mockResolvedValue([])
		const component = await mount()
		expect(component.getByText(/This folder is no longer available/)).toBeVisible()
		expect(component.queryByRole('button', { name: 'Open folder' })).toBeNull()
		await fireEvent.click(component.getByRole('button', { name: 'Choose folder' }))
		expect(builder.startAt).toHaveBeenCalledWith('/')
		expect(axios.put).not.toHaveBeenCalled()
	})

	it('allows retrying a failed load', async () => {
		vi.mocked(axios.get).mockRejectedValueOnce(new Error('Offline'))
		const component = render(FilesAppSettingsTemplates)
		await waitFor(() => expect(component.getByText('Unable to load the template folder')).toBeVisible())
		expect(component.getByRole('button', { name: 'Choose folder' })).toBeDisabled()
		await fireEvent.click(component.getByRole('button', { name: 'Retry' }))
		await waitFor(() => expect(component.getByText('/Templates')).toBeVisible())
		expect(component.getByRole('button', { name: 'Choose folder' })).not.toBeDisabled()
	})
})
