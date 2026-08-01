/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import UserColumnResizer from './UserColumnResizer.vue'
import { COLUMN_MIN_WIDTH, COLUMN_RESIZE_STEP } from '../../utils/userListColumns.ts'

const CELL_WIDTH = 300

// jsdom has no PointerEvent and wrapper.trigger() cannot set the read-only
// clientX, so dispatch a MouseEvent carrying the pointer event type instead
function firePointerEvent(element: Element, type: string, clientX: number) {
	element.dispatchEvent(new MouseEvent(type, { bubbles: true, clientX }))
}

function mountInHeaderCell() {
	const wrapper = mount({
		components: { UserColumnResizer },
		template: `
			<table>
				<tr>
					<th>
						<UserColumnResizer column="email" label="Email" />
					</th>
				</tr>
			</table>
		`,
	})
	wrapper.find('th').element.getBoundingClientRect = () => ({ width: CELL_WIDTH } as DOMRect)
	return wrapper
}

describe('UserColumnResizer', () => {
	it('widens the column with the right arrow key', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		await resizer.trigger('keydown', { key: 'ArrowRight' })

		expect(resizer.emitted('resize')).toEqual([[CELL_WIDTH + COLUMN_RESIZE_STEP]])
		expect(resizer.emitted('resize-end')).toHaveLength(1)
	})

	it('narrows the column with the left arrow key', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		await resizer.trigger('keydown', { key: 'ArrowLeft' })

		expect(resizer.emitted('resize')).toEqual([[CELL_WIDTH - COLUMN_RESIZE_STEP]])
	})

	it('ignores unrelated keys', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		await resizer.trigger('keydown', { key: 'Enter' })

		expect(resizer.emitted('resize')).toBeUndefined()
	})

	it('resizes on pointer drag and clamps to the minimum width', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		firePointerEvent(resizer.element, 'pointerdown', 500)
		firePointerEvent(resizer.element, 'pointermove', 550)
		firePointerEvent(resizer.element, 'pointermove', 100)
		firePointerEvent(resizer.element, 'pointerup', 100)
		await resizer.vm.$nextTick()

		expect(resizer.emitted('resize')).toEqual([[CELL_WIDTH + 50], [COLUMN_MIN_WIDTH]])
		expect(resizer.emitted('resize-end')).toHaveLength(1)
	})

	it('does not resize on pointer move without a preceding pointer down', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		firePointerEvent(resizer.element, 'pointermove', 550)
		await resizer.vm.$nextTick()

		expect(resizer.emitted('resize')).toBeUndefined()
	})

	it('emits a reset on double click', async () => {
		const wrapper = mountInHeaderCell()
		const resizer = wrapper.findComponent(UserColumnResizer)

		await resizer.trigger('dblclick')

		expect(resizer.emitted('reset')).toHaveLength(1)
	})
})
