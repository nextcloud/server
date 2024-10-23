/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node } from '@nextcloud/files'
import { FileAction } from '@nextcloud/files'
import { subscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import '../css/fileEntryInlineSystemTags.scss'
import { getNodeSystemTags } from '../utils'

const renderTag = function(tag: string, isMore = false): HTMLElement {
	const tagElement = document.createElement('li')
	tagElement.classList.add('files-list__system-tag')
	tagElement.textContent = tag

	if (isMore) {
		tagElement.classList.add('files-list__system-tag--more')
	}

	return tagElement
}

const renderInline = async function(node: Node): Promise<HTMLElement> {
	// Ensure we have the system tags as an array
	const tags = getNodeSystemTags(node)

	const systemTagsElement = document.createElement('ul')
	systemTagsElement.classList.add('files-list__system-tags')
	systemTagsElement.setAttribute('aria-label', t('files', 'Assigned collaborative tags'))
	systemTagsElement.setAttribute('data-systemtags-fileid', node.fileid?.toString() || '')

	if (tags.length === 0) {
		return systemTagsElement
	}

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

		// Always show the action, even if there are no tags
		// This will render an empty tag list and allow events to update it
		return true
	},

	exec: async () => null,
	renderInline,

	order: 0,
})

const updateSystemTagsHtml = function(node: Node) {
	renderInline(node).then((systemTagsHtml) => {
		document.querySelectorAll(`[data-systemtags-fileid="${node.fileid}"]`).forEach((element) => {
			element.replaceWith(systemTagsHtml)
		})
	})
}

subscribe('systemtags:node:updated', updateSystemTagsHtml)
