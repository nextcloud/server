/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import { FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'

import '../css/fileEntryInlineSystemTags.scss'

const getNodeSystemTags = function(node: Node): string[] {
	const tags = node.attributes?.['system-tags']?.['system-tag'] as string|string[]|undefined

	if (tags === undefined) {
		return []
	}

	return [tags].flat()
}

const renderTag = function(tag: string, isMore = false): HTMLElement {
	const tagElement = document.createElement('li')
	tagElement.classList.add('files-list__system-tag')
	tagElement.textContent = tag

	if (isMore) {
		tagElement.classList.add('files-list__system-tag--more')
	}

	return tagElement
}

export const action = new FileAction({
	id: 'system-tags',
	displayName: () => '',
	iconSvgInline: () => '',

	enabled(nodes: Node[]) {
		// Only show the action on single nodes
		if (nodes.length !== 1) {
			return false
		}

		const node = nodes[0]
		const tags = getNodeSystemTags(node)

		// Only show the action if the node has system tags
		if (tags.length === 0) {
			return false
		}

		return true
	},

	exec: async () => null,

	async renderInline(node: Node) {
		// Ensure we have the system tags as an array
		const tags = getNodeSystemTags(node)

		if (tags.length === 0) {
			return null
		}

		const systemTagsElement = document.createElement('ul')
		systemTagsElement.classList.add('files-list__system-tags')
		systemTagsElement.setAttribute('aria-label', t('files', 'Assigned collaborative tags'))

		systemTagsElement.append(renderTag(tags[0]))
		if (tags.length === 2) {
			// Special case only two tags:
			// the overflow fake tag would take the same space as this, so render it
			systemTagsElement.append(renderTag(tags[1]))
		} else if (tags.length > 1) {
			// More tags than the one we're showing
			// So we add a overflow element indicating there are more tags
			const moreTagElement = renderTag('+' + (tags.length - 1), true)
			moreTagElement.setAttribute('title', tags.slice(1).join(', '))
			// because the title is not accessible we hide this element for screen readers (see alternative below)
			moreTagElement.setAttribute('aria-hidden', 'true')
			moreTagElement.setAttribute('role', 'presentation')
			systemTagsElement.append(moreTagElement)

			// For accessibility the tags are listed, as the title is not accessible
			// but those tags are visually hidden
			for (const tag of tags.slice(1)) {
				const tagElement = renderTag(tag)
				tagElement.classList.add('hidden-visually')
				systemTagsElement.append(tagElement)
			}
		}

		return systemTagsElement
	},

	order: 0,
})
