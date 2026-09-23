/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { Folder, Permission } from '@nextcloud/files'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { setTemplateDirectory, templateDirectory } from '../store/templateDirectory.ts'
import { newNodeName } from '../utils/newNodeDialog.ts'
import { entry } from './newTemplatesFolder.ts'

vi.mock('@nextcloud/auth', () => ({ getCurrentUser: () => ({ uid: 'alice' }) }))
vi.mock('@nextcloud/axios', () => ({ default: { post: vi.fn(), put: vi.fn() } }))
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn() }))
vi.mock('../utils/newNodeDialog.ts', () => ({ newNodeName: vi.fn() }))

const folder = new Folder({ source: 'http://localhost/remote.php/dav/files/alice/', owner: 'alice', permissions: Permission.ALL, root: '/files/alice' })

describe('Create templates folder menu entry', () => {
	beforeEach(() => {
		vi.clearAllMocks()
		Object.assign(templateDirectory, { template_path: '', available: false })
		window.OCP.Files = { Router: { goToRoute: vi.fn() } } as unknown as typeof window.OCP.Files
	})

	it('reflects selection and clearing from settings without a reload', async () => {
		expect(entry.enabled!(folder)).toBe(true)
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { template_path: '/Templates', available: true } } } })
		await setTemplateDirectory('/Templates')
		expect(entry.enabled!(folder)).toBe(false)
		vi.mocked(axios.put).mockResolvedValueOnce({ data: { ocs: { data: { template_path: '', available: false } } } })
		await setTemplateDirectory('')
		expect(entry.enabled!(folder)).toBe(true)
	})

	it('updates the shared selection after initialization succeeds', async () => {
		vi.mocked(newNodeName).mockResolvedValue('Templates')
		vi.mocked(axios.post).mockResolvedValue({ data: { ocs: { data: { template_path: '/Templates' } } } })
		await entry.handler(folder, [])
		expect(templateDirectory).toEqual({ template_path: '/Templates', available: true })
		expect(entry.enabled!(folder)).toBe(false)
	})

	it('keeps the entry available after initialization fails', async () => {
		vi.mocked(newNodeName).mockResolvedValue('Templates')
		vi.mocked(axios.post).mockRejectedValue(new Error('Failed'))
		await entry.handler(folder, [])
		expect(entry.enabled!(folder)).toBe(true)
		expect(window.OCP.Files.Router.goToRoute).not.toHaveBeenCalled()
	})

	it('does not initialize when cancelled', async () => {
		vi.mocked(newNodeName).mockResolvedValue(null)
		await entry.handler(folder, [])
		expect(axios.post).not.toHaveBeenCalled()
	})
})
