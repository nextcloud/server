/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VueWrapper } from '@vue/test-utils'

import { findByLabelText, findByRole, fireEvent, getByLabelText, getByRole } from '@testing-library/vue'
import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import RemoteShareDialog from './RemoteShareDialog.vue'

describe('RemoteShareDialog', () => {
	let component: VueWrapper

	afterEach(() => {
		component?.unmount()
	})

	it('can be mounted', async () => {
		component = mount(RemoteShareDialog, {
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
			attachTo: 'body',
		})

		await expect(findByRole(document.body, 'dialog', { name: 'Remote share' })).resolves.not.toThrow()
	})

	it('does not show password input if not enabled', async () => {
		component = mount(RemoteShareDialog, {
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		await expect(findByLabelText(document.body, 'Remote share password')).rejects.toThrow()
	})

	it('emits true when accepted', async () => {
		const onClose = vi.fn()

		component = mount(RemoteShareDialog, {
			attrs: {
				onClose,
			},
			props: {
				owner: 'user123',
				name: 'my-photos',
				remote: 'nextcloud.local',
				passwordRequired: false,
			},
		})

		const button = getByRole(document.body, 'button', { name: 'Cancel' })
		await fireEvent.click(button)
		expect(onClose).toHaveBeenCalledWith(false)
	})

	it('show password input if needed', async () => {
		component = mount(RemoteShareDialog, {
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		await expect(findByLabelText(document.body, 'Remote share password')).resolves.not.toThrow()
	})

	it('emits the submitted password', async () => {
		const onClose = vi.fn()

		component = mount(RemoteShareDialog, {
			attrs: {
				onClose,
			},
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		const input = getByLabelText(document.body, 'Remote share password')
		await fireEvent.update(input, 'my password')
		const button = getByRole(document.body, 'button', { name: 'Add remote share' })
		await fireEvent.click(button)
		expect(onClose).toHaveBeenCalledWith(true, 'my password')
	})

	it('emits no password if cancelled', async () => {
		const onClose = vi.fn()

		component = mount(RemoteShareDialog, {
			attrs: {
				onClose,
			},
			props: {
				owner: 'admin',
				name: 'secret-data',
				remote: 'nextcloud.local',
				passwordRequired: true,
			},
		})

		const input = getByLabelText(document.body, 'Remote share password')
		await fireEvent.update(input, 'my password')
		const button = getByRole(document.body, 'button', { name: 'Cancel' })
		await fireEvent.click(button)
		expect(onClose).toHaveBeenCalledWith(false)
	})
})
