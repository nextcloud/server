/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, fireEvent, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RemoteShareDialog from './RemoteShareDialog.vue'

describe('RemoteShareDialog', () => {
	beforeEach(cleanup)

	it('can be mounted', async () => {
		const component = render(RemoteShareDialog, {
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		await expect(component.findByRole('dialog', { name: 'Remote share' })).resolves.not.toThrow()
		expect(component.getByRole('dialog').innerText).toContain(/my-photos from user123@nextcloud.local/)
		await expect(component.findByRole('button', { name: 'Cancel' })).resolves.not.toThrow()
		await expect(component.findByRole('button', { name: /Add remote share/ })).resolves.not.toThrow()
	})

	it('does not show password input if not enabled', async () => {
		const component = render(RemoteShareDialog, {
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		await expect(component.findByLabelText('Remote share password')).rejects.toThrow()
	})

	it('emits true when accepted', () => {
		const onClose = vi.fn()

		const component = render(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		component.getByRole('button', { name: 'Cancel' }).click()
		expect(onClose).toHaveBeenCalledWith(false)
	})

	it('show password input if needed', async () => {
		const component = render(RemoteShareDialog, {
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		await expect(component.findByLabelText('Remote share password')).resolves.not.toThrow()
	})

	it('emits the submitted password', async () => {
		const onClose = vi.fn()

		const component = render(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		const input = component.getByLabelText('Remote share password')
		await fireEvent.update(input, 'my password')
		component.getByRole('button', { name: 'Add remote share' }).click()
		expect(onClose).toHaveBeenCalledWith(true, 'my password')
	})

	it('emits no password if cancelled', async () => {
		const onClose = vi.fn()

		const component = render(RemoteShareDialog, {
			listeners: {
				close: onClose,
			},
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		const input = component.getByLabelText('Remote share password')
		await fireEvent.update(input, 'my password')
		component.getByRole('button', { name: 'Cancel' }).click()
		expect(onClose).toHaveBeenCalledWith(false)
	})
})
