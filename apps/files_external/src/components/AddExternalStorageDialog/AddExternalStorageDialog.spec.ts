/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IStorage } from '../../types.ts'

import axios from '@nextcloud/axios'
import { cleanup, render } from '@testing-library/vue'
import { createPinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/axios')

// The dialog's auth/backend config children resolve custom field handlers from
// this global, which the app registers at runtime.
vi.hoisted(() => {
	const registry = { getHandler: () => undefined }
	// @ts-expect-error minimal stub of the runtime global
	window.OCA = { FilesExternal: { AuthMechanism: registry, Backend: registry } }
})

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string) => {
		switch (key) {
			case 'backends':
				return [{ identifier: 'local', name: 'Local', configuration: {}, authSchemes: { null: true } }]
			case 'authMechanisms':
				return [{ identifier: 'null::null', name: 'None', scheme: 'null', configuration: {} }]
			case 'allowedBackends':
				return ['local']
			default:
				return { isAdmin: true, hasEncryption: false }
		}
	},
}))

const { default: AddExternalStorageDialog } = await import('./AddExternalStorageDialog.vue')

const pinia = createPinia()

const storage: Partial<IStorage> = {
	mountPoint: '/mount',
	backend: 'local',
	authMechanism: 'null::null',
	backendOptions: {},
	mountOptions: {},
	type: 'system',
}

/**
 * Render the dialog for a given storage
 *
 * @param overrides - Storage fields to override
 */
function renderDialog(overrides: Partial<IStorage> = {}) {
	return render(AddExternalStorageDialog, {
		props: { storage: { ...storage, ...overrides } },
		global: { plugins: [pinia] },
	})
}

const WARNING = /available to every account/

describe('AddExternalStorageDialog.vue', () => {
	beforeEach(() => {
		cleanup()
		vi.spyOn(axios, 'get').mockResolvedValue({ data: { groups: {}, users: {} } })
		vi.spyOn(axios, 'post').mockResolvedValue({ data: { users: {} } })
	})

	// An empty applicable list is not a restriction to nobody: the storage is
	// mounted for everyone, so the dialog has to say so before it is saved.
	it('warns that an empty restriction applies to every account', () => {
		const component = renderDialog({ applicableUsers: [], applicableGroups: [] })

		expect(component.getByText(WARNING)).toBeInTheDocument()
	})

	it('does not warn once a group restricts the storage', () => {
		const component = renderDialog({ applicableGroups: ['developers'] })

		expect(component.queryByText(WARNING)).toBeNull()
	})

	it('does not warn once a user restricts the storage', () => {
		const component = renderDialog({ applicableUsers: ['alice'] })

		expect(component.queryByText(WARNING)).toBeNull()
	})
})
