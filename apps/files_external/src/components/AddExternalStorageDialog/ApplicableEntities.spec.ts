/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { cleanup, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ApplicableEntities from './ApplicableEntities.vue'

vi.mock('@nextcloud/axios')

describe('ApplicableEntities.vue', () => {
	beforeEach(() => {
		cleanup()
		// useGroups and useUsers resolve display names over axios
		vi.spyOn(axios, 'get').mockResolvedValue({ data: { groups: {}, users: {} } })
		vi.spyOn(axios, 'post').mockResolvedValue({ data: { users: {} } })
	})

	it('warns that an empty restriction applies to every account', () => {
		const component = render(ApplicableEntities, { props: { groups: [], users: [] } })

		expect(component.getByRole('note')).toHaveTextContent(/available to every account/)
	})

	it('does not warn once a group restricts the storage', () => {
		const component = render(ApplicableEntities, { props: { groups: ['admin'], users: [] } })

		expect(component.queryByRole('note')).toBeNull()
	})

	it('does not warn once a user restricts the storage', () => {
		const component = render(ApplicableEntities, { props: { groups: [], users: ['alice'] } })

		expect(component.queryByRole('note')).toBeNull()
	})

	it('warns again as soon as the last entry is removed', async () => {
		const component = render(ApplicableEntities, { props: { groups: ['admin'], users: ['alice'] } })

		expect(component.queryByRole('note')).toBeNull()

		await component.rerender({ users: [] })
		expect(component.queryByRole('note')).toBeNull()

		await component.rerender({ groups: [] })
		expect(component.getByRole('note')).toHaveTextContent(/available to every account/)
	})
})
