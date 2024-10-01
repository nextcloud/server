/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import DragAndDropPreview from '../components/DragAndDropPreview.vue'
import Vue from 'vue'

const Preview = Vue.extend(DragAndDropPreview)
let preview: Vue

export const getDragAndDropPreview = async (nodes: Node[]): Promise<Element> => {
	return new Promise((resolve) => {
		if (!preview) {
			preview = new Preview().$mount()
			document.body.appendChild(preview.$el)
		}

		preview.update(nodes)
		preview.$on('loaded', () => {
			resolve(preview.$el)
			preview.$off('loaded')
		})
	})
}
