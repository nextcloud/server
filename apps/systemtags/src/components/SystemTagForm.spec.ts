/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { VueWrapper } from '@vue/test-utils'
import type { TagWithId } from '../types.ts'

import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import SystemTagForm from './SystemTagForm.vue'

const tags: TagWithId[] = Array.from({ length: 8 }, (_, index) => ({
	id: index + 1,
	displayName: `tag ${index + 1}`,
	userVisible: true,
	userAssignable: true,
	canAssign: true,
}))

describe('SystemTagForm', () => {
	let component: VueWrapper

	afterEach(() => {
		component?.unmount()
	})

	it('offers every tag for editing', () => {
		component = mount(SystemTagForm, { props: { tags } })

		const select = component.findComponent({ name: 'VueSelect' })

		expect(select.vm.filteredOptions).toHaveLength(tags.length)
	})
})
