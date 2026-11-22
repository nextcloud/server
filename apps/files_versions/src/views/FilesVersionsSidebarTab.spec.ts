/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

const viewer = vi.hoisted(() => ({
	canCompare: vi.fn(() => true),
	canView: vi.fn(() => true),
	getViewer: vi.fn(),
}))
vi.mock('@nextcloud/viewer', () => viewer)
// On a phone there is no room for two versions side by side
vi.mock('@nextcloud/vue/composables/useIsMobile', async () => {
	const { ref } = await import('vue')
	return { useIsMobile: () => ref(false) }
})

vi.mock('../utils/versions.ts', () => ({
	fetchVersions: vi.fn(async () => [{ mtime: 1, fileVersion: '1', label: '', basename: 'notes.md' }]),
	deleteVersion: vi.fn(),
	restoreVersion: vi.fn(),
	setVersionLabel: vi.fn(),
	versionToNode: vi.fn(),
}))

// The list renders its rows in a slot only once it has measured itself:
// render them all here, the entries are what is tested
vi.mock('../components/VirtualScrolling.vue', () => ({
	default: defineComponent({
		props: ['sections', 'headerHeight'],
		setup(props, { slots }) {
			return () => h('div', slots.default?.({ visibleSections: props.sections }))
		},
	}),
}))

vi.mock('../components/VersionEntry.vue', () => ({
	default: defineComponent({
		name: 'VersionEntry',
		props: ['canView', 'canCompare', 'loadPreview', 'version', 'node', 'isCurrent', 'isFirstVersion'],
		setup: () => () => h('li'),
	}),
}))

import FilesVersionsSidebarTab from './FilesVersionsSidebarTab.vue'

/**
 * Show the versions tab of a markdown file.
 */
async function mountTab() {
	const node = { id: 1, fileid: 1, mime: 'text/markdown', mtime: new Date(2), basename: 'notes.md' }
	const wrapper = mount(FilesVersionsSidebarTab, { props: { active: true, node } as never })
	await flushPromises()
	return { wrapper, node }
}

beforeEach(() => vi.clearAllMocks())

describe('comparing two versions', () => {
	// It used to be offered for pictures only, until the viewer could say
	// which of its handlers compare: Text does, and is not a picture
	it('is offered where the viewer says it is worth it', async () => {
		viewer.canCompare.mockReturnValue(true)
		const { wrapper, node } = await mountTab()

		expect(viewer.canCompare).toHaveBeenCalledWith(node)
		expect(wrapper.findComponent({ name: 'VersionEntry' }).props('canCompare')).toBe(true)
	})

	it('is not offered where the viewer says it is not', async () => {
		viewer.canCompare.mockReturnValue(false)
		const { wrapper } = await mountTab()

		expect(wrapper.findComponent({ name: 'VersionEntry' }).props('canCompare')).toBe(false)
	})
})
