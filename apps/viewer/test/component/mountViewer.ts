import type { File } from '@nextcloud/files'
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { VueWrapper } from '@vue/test-utils'
import type { IHandler } from '../../src/api_package/index.ts'
import type { ViewerOptions } from '../../src/api_package/viewer.ts'

import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'
import Viewer from '../../src/views/Viewer.vue'
import { registerTestHandlers } from '../factories.ts'

/**
 * Minimal NcModal stub.
 *
 * It renders the default slot and the `#actions` slot so we can assert on the
 * handler custom-element markup and drive the header actions. Navigation is
 * driven from tests through `findComponent(NcModalStub).vm.$emit('next'|'previous'|'close')`.
 * The relevant props (`name`, `show`, `hasNext`, `hasPrevious`, `isComparing`
 * related flags) are declared so they can be read back via `.props()`.
 */
export const NcModalStub = defineComponent({
	name: 'NcModal',
	inheritAttrs: false,
	props: {
		name: { type: String, default: '' },
		show: { type: Boolean, default: false },
		hasNext: { type: Boolean, default: false },
		hasPrevious: { type: Boolean, default: false },
		enableSlideshow: { type: Boolean, default: false },
		lightBackdrop: { type: Boolean, default: false },
	},
	emits: ['next', 'previous', 'close'],
	template: `
		<div
			class="nc-modal-stub"
			:data-handler="$attrs['data-handler']"
			:data-name="name"
			:data-show="String(show)"
			:data-has-next="String(hasNext)"
			:data-has-previous="String(hasPrevious)">
			<div class="nc-modal-stub__actions"><slot name="actions" /></div>
			<div class="nc-modal-stub__content"><slot /></div>
		</div>
	`,
})

const NcActionButtonStub = defineComponent({
	name: 'NcActionButton',
	emits: ['click'],
	template: '<button class="nc-action-button-stub" @click="$emit(\'click\', $event)"><slot name="icon" /><slot /></button>',
})

const NcEmptyContentStub = defineComponent({
	name: 'NcEmptyContent',
	props: {
		name: { type: String, default: '' },
		description: { type: String, default: '' },
	},
	template: '<div class="nc-empty-content-stub" :data-name="name">{{ name }}<slot name="icon" /><slot /></div>',
})

const NcLoadingIconStub = defineComponent({
	name: 'NcLoadingIcon',
	template: '<span class="nc-loading-icon-stub" />',
})

const IconStub = defineComponent({
	name: 'IconStub',
	render() {
		return h('span', { class: 'icon-stub' })
	},
})

export interface MountViewerResult {
	wrapper: VueWrapper
	/** The Viewer instance's exposed API + internal component vm. */
	vm: any
	/** Emit an NcModal event (next|previous|close) to drive navigation. */
	emitModal: (event: 'next' | 'previous' | 'close') => Promise<void>
	/** Read the modal `data-handler` attribute. */
	modalHandlerId: () => string | undefined
	/** Read the modal name (basename / comparison title). */
	modalName: () => string | undefined
	/** Props currently passed to the NcModal stub. */
	modalProps: () => Record<string, unknown>
	/** All rendered handler custom-element tag names (e.g. `oca-viewer-test`). */
	renderedTags: () => string[]
	/** Whether the error empty-content is shown, and its message. */
	errorText: () => string | undefined
}

/**
 * Mount the Viewer with lightweight stubs and the given handlers registered.
 *
 * @param handlers - Handlers to register before mounting. When omitted the
 *   caller is expected to have registered handlers already.
 */
export function mountViewer(handlers: IHandler[] = []): MountViewerResult {
	if (handlers.length > 0) {
		registerTestHandlers(...handlers)
	}

	const wrapper = mount(Viewer, {
		attachTo: document.body,
		global: {
			stubs: {
				NcModal: NcModalStub,
				NcActionButton: NcActionButtonStub,
				NcEmptyContent: NcEmptyContentStub,
				NcIconSvgWrapper: IconStub,
				NcLoadingIcon: NcLoadingIconStub,
				ChevronLeft: IconStub,
				DockRight: IconStub,
				FileAlertOutlineIcon: IconStub,
			},
		},
	})

	const findModal = () => wrapper.findComponent(NcModalStub)

	const emitModal = async (event: 'next' | 'previous' | 'close') => {
		findModal().vm.$emit(event)
		await wrapper.vm.$nextTick()
	}

	const renderedTags = () => {
		const html = wrapper.html()
		const matches = html.match(/<(oca-viewer-[a-z0-9-]+)/g) ?? []
		return matches.map((m) => m.replace('<', ''))
	}

	return {
		wrapper,
		vm: wrapper.vm as any,
		emitModal,
		modalHandlerId: () => findModal().attributes('data-handler'),
		modalName: () => findModal().attributes('data-name'),
		modalProps: () => findModal().props(),
		renderedTags,
		errorText: () => {
			const ec = wrapper.find('.nc-empty-content-stub')
			return ec.exists() ? ec.attributes('data-name') : undefined
		},
	}
}

export type { File, ViewerOptions }
