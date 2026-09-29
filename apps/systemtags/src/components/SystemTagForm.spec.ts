/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Wrapper } from '@vue/test-utils'
import type Vue from 'vue'
import type { TagWithId } from '../types.ts'

// eslint-disable-next-line n/no-extraneous-import -- transitive dependency of @nextcloud/vue, needed to identify the rendered select in tests
import { VueSelect } from '@nextcloud/vue-select'
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
	let component: Wrapper<Vue>

	afterEach(() => {
		component?.destroy()
	})

	it('offers every tag for editing', () => {
		component = mount(SystemTagForm, { propsData: { tags } })

		const select = component.findComponent(VueSelect)

		expect(select.vm.filteredOptions).toHaveLength(tags.length)
	})
})
