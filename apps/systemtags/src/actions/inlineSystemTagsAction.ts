/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { FileAction, Node, registerDavProperty, registerFileAction } from '@nextcloud/files'
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

		if (tags.length === 1) {
			systemTagsElement.setAttribute('aria-label', t('files', 'This file has the tag {tag}', { tag: tags[0] }))
		} else {
			const firstTags = tags.slice(0, -1).join(', ')
			const lastTag = tags[tags.length - 1]
			systemTagsElement.setAttribute('aria-label', t('files', 'This file has the tags {firstTags} and {lastTag}', { firstTags, lastTag }))
		}

		systemTagsElement.append(renderTag(tags[0]))

		// More tags than the one we're showing
		if (tags.length > 1) {
			const moreTagElement = renderTag('+' + (tags.length - 1), true)
			moreTagElement.setAttribute('title', tags.slice(1).join(', '))
			systemTagsElement.append(moreTagElement)
		}

		return systemTagsElement
	},

	order: 0,
})

registerDavProperty('nc:system-tags')
registerFileAction(action)
