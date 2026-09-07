/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IStorage } from '../types.ts'

import axios from '@nextcloud/axios'
import { cleanup, render } from '@testing-library/vue'
import { createPinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/axios')

vi.mock('@nextcloud/initial-state', () => ({
	loadState: (app: string, key: string) => {
		switch (key) {
			case 'backends':
				return [{ identifier: 'local', name: 'Local' }]
			case 'authMechanisms':
				return [{ identifier: 'null::null', name: 'None', scheme: 'null' }]
			case 'allowedBackends':
				return ['local']
			default:
				return { isAdmin: true, hasEncryption: false }
		}
	},
}))

const { default: ExternalStorageTableRow } = await import('./ExternalStorageTableRow.vue')

const pinia = createPinia()

const storage: IStorage = {
	id: 1,
	mountPoint: '/mount',
	backend: 'local',
	authMechanism: 'null::null',
	backendOptions: {},
	userProvided: false,
	type: 'system',
}

// Without a table ancestor the tds get no `cell` role, so getByRole cannot find them.
function renderRow(props: { storage: IStorage, isAdmin: boolean }) {
	const table = document.body.appendChild(document.createElement('table'))
	const tbody = table.appendChild(document.createElement('tbody'))

	return render(ExternalStorageTableRow, {
		container: tbody,
		props,
		global: { plugins: [pinia] },
	})
}

describe('ExternalStorageTableRow.vue', () => {
	beforeEach(() => {
		cleanup()
		// cleanup() only drops containers it owns, not the tables renderRow appends
		document.body.replaceChildren()
		// useGroups and useUsers resolve display names over axios
		vi.spyOn(axios, 'get').mockResolvedValue({ data: { groups: {} } })
		vi.spyOn(axios, 'post').mockResolvedValue({ data: { users: {} } })
	})

	it('labels a storage without any restriction as applying to all accounts', () => {
		const component = renderRow({ storage, isAdmin: true })

		expect(component.getByRole('cell', { name: 'All accounts' })).toBeInTheDocument()
	})

	it('lists the groups a storage is restricted to', () => {
		const component = renderRow({
			storage: { ...storage, applicableGroups: ['developers'] },
			isAdmin: true,
		})

		expect(component.getByRole('cell', { name: 'developers' })).toBeInTheDocument()
		expect(component.queryByRole('cell', { name: 'All accounts' })).toBeNull()
	})

	it('lists the users a storage is restricted to', () => {
		const component = renderRow({
			storage: { ...storage, applicableUsers: ['alice'] },
			isAdmin: true,
		})

		expect(component.getByRole('cell', { name: 'alice' })).toBeInTheDocument()
		expect(component.queryByRole('cell', { name: 'All accounts' })).toBeNull()
	})

	it('omits the applicable cell for non-admins', () => {
		const component = renderRow({ storage, isAdmin: false })

		expect(component.queryByRole('cell', { name: 'All accounts' })).toBeNull()
	})
})
