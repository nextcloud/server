/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent } from 'vue'
import { makeFile } from '../factories.ts'

const axiosPut = vi.hoisted(() => vi.fn())
vi.mock('@nextcloud/axios', () => ({ default: { put: axiosPut } }))
vi.mock('@nextcloud/event-bus')
vi.mock('@nextcloud/dialogs', () => ({ showError: vi.fn(), showSuccess: vi.fn() }))
// Avoid loading the real (canvas/webgl) editor; expose a stub that re-emits.
vi.mock('@nextcloud/image-editor', () => ({
	ImageEditor: defineComponent({
		name: 'LibImageEditor',
		emits: ['save', 'cancel', 'error'],
		template: '<div class="image-editor-stub" />',
	}),
}))

import { emit } from '@nextcloud/event-bus'
import ImageEditor from '../../src/components/ImageEditor.vue'

describe('ImageEditor wrapper', () => {
	beforeEach(() => {
		axiosPut.mockReset()
		axiosPut.mockResolvedValue({ headers: { 'oc-etag': '"abc123"' } })
	})

	const mountEditor = () => {
		const file = makeFile({ basename: 'photo.jpg', mime: 'image/jpeg' })
		const wrapper = mount(ImageEditor, { props: { file } })
		return { wrapper, file, editor: wrapper.findComponent({ name: 'LibImageEditor' }) }
	}

	it('saves the exported blob over the file, emits its object URL and refreshes the node', async () => {
		vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:edited')
		const { wrapper, file, editor } = mountEditor()
		const blob = new Blob(['x'], { type: 'image/jpeg' })

		editor.vm.$emit('save', { blob, width: 1, height: 1, mimeType: 'image/jpeg' })
		await flushPromises()

		expect(axiosPut).toHaveBeenCalledWith(file.encodedSource, blob)
		expect(file.attributes.etag).toBe('abc123')
		expect(wrapper.emitted('saved')).toEqual([['blob:edited']])
		expect(vi.mocked(emit)).toHaveBeenCalledWith('files:node:updated', file)
		expect(wrapper.emitted('close')).toBeTruthy()
	})

	it('closes without saving on cancel', async () => {
		const { wrapper, editor } = mountEditor()
		editor.vm.$emit('cancel')
		await flushPromises()
		expect(axiosPut).not.toHaveBeenCalled()
		expect(wrapper.emitted('close')).toBeTruthy()
	})
})
