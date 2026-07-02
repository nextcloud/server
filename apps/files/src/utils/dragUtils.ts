/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import type { ComponentPublicInstance } from 'vue'

import { createApp } from 'vue'
import DragAndDropPreview from '../components/DragAndDropPreview.vue'

type PreviewInstance = ComponentPublicInstance & { update: (nodes: Node[]) => void }

let preview: PreviewInstance | undefined
let onLoaded: ((el: Element) => void) | undefined

/**
 *
 * @param nodes
 */
export async function getDragAndDropPreview(nodes: Node[]): Promise<Element> {
	return new Promise((resolve) => {
		if (!preview) {
			const mountingPoint = document.createElement('div')
			document.body.appendChild(mountingPoint)
			const app = createApp(DragAndDropPreview, {
				onLoaded: (el: Element) => onLoaded?.(el),
			})
			preview = app.mount(mountingPoint) as PreviewInstance
		}

		onLoaded = resolve
		preview.update(nodes)
	})
}
