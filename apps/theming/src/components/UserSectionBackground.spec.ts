/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import UserSectionBackground from './UserSectionBackground.vue'

const allowDirectories = vi.hoisted(() => vi.fn<(allow: boolean) => unknown>())
const setMimeTypeFilter = vi.hoisted(() => vi.fn<(mimes: string[]) => unknown>())

vi.mock('@nextcloud/dialogs', () => ({
	getFilePickerBuilder: vi.fn(() => {
		const builder = {
			allowDirectories,
			setMimeTypeFilter,
			setMultiSelect: vi.fn(),
			addButton: vi.fn(),
			build: vi.fn(),
		}

		builder.allowDirectories.mockReturnValue(builder)
		builder.setMimeTypeFilter.mockReturnValue(builder)
		builder.setMultiSelect.mockReturnValue(builder)
		builder.addButton.mockReturnValue(builder)
		builder.build.mockReturnValue({
			pick: vi.fn().mockResolvedValue(undefined),
		})

		return builder
	}),
}))

vi.mock('@nextcloud/initial-state', () => ({
	loadState: vi.fn((_app: string, key: string) => {
		switch (key) {
		case 'shippedBackgrounds':
			return {}
		case 'themingDefaults':
			return {
				backgroundImage: '',
				backgroundColor: '#ffffff',
				backgroundMime: '',
				defaultShippedBackground: '',
			}
		case 'data':
			return {
				backgroundImage: '',
				backgroundColor: '#ffffff',
				backgroundMime: '',
			}
		case 'userBackgroundImage':
			return 'default'
		default:
			throw new Error(`Unexpected initial state key: ${key}`)
		}
	}),
}))

vi.mock('@nextcloud/l10n', () => ({
	t: (_app: string, text: string) => text,
}))

describe('UserSectionBackground', () => {
	it('opens the picker filtered to images while keeping folders navigable', async () => {
		const wrapper = shallowMount(UserSectionBackground, {
			global: {
				stubs: {
					NcSettingsSection: {
						template: '<section><slot /></section>',
					},
				},
			},
		})

		await wrapper.get('button[aria-label="Custom background"]').trigger('click')

		// A mime-type filter keeps folders visible for navigation while
		// restricting pickable entries to images. Regression guard for the
		// empty file dialog caused by a node filter that also hid folders.
		expect(setMimeTypeFilter).toHaveBeenCalledOnce()
		expect(setMimeTypeFilter).toHaveBeenCalledWith(['image/*'])
		expect(allowDirectories).toHaveBeenCalledWith(false)
	})
})
