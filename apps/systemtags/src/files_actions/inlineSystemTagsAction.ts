/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'
import type { TagWithId } from '../types.ts'

import { subscribe } from '@nextcloud/event-bus'
import { FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import logger from '../logger.ts'
import { fetchTags } from '../services/api.ts'
import { getNodeSystemTags } from '../utils.ts'
import { elementColor, isDarkModeEnabled } from '../utils/colorUtils.ts'

import '../css/fileEntryInlineSystemTags.scss'

// Init tag cache
const cache: TagWithId[] = []

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

	hotkey: {
		description: t('files', 'Manage tags'),
		key: 'T',
	},
})

// Subscribe to the events
subscribe('systemtags:node:updated', updateSystemTagsHtml)
subscribe('systemtags:tag:created', addTag)
subscribe('systemtags:tag:deleted', removeTag)
subscribe('systemtags:tag:updated', updateTag)

/**
 * Update the system tags html when the node is updated
 *
 * @param node - The updated node
 */
function updateSystemTagsHtml(node: Node) {
	renderInline(node).then((systemTagsHtml) => {
		document.querySelectorAll(`[data-systemtags-fileid="${node.fileid}"]`).forEach((element) => {
			element.replaceWith(systemTagsHtml)
		})
	})
}

/**
 * Add and remove tags from the cache
 *
 * @param tag - The tag to add
 */
function addTag(tag: TagWithId) {
	cache.push(tag)
}

/**
 * Remove a tag from the cache
 *
 * @param tag - The tag to remove
 */
function removeTag(tag: TagWithId) {
	cache.splice(cache.findIndex((t) => t.id === tag.id), 1)
}

/**
 * Update a tag in the cache
 *
 * @param tag - The tag to update
 */
function updateTag(tag: TagWithId) {
	const index = cache.findIndex((t) => t.id === tag.id)
	if (index !== -1) {
		cache[index] = tag
	}
	updateSystemTagsColorAttribute(tag)
}

/**
 * Update the color attribute of the system tags
 *
 * @param tag - The tag to update
 */
function updateSystemTagsColorAttribute(tag: TagWithId) {
	document.querySelectorAll(`[data-systemtag-name="${tag.displayName}"]`).forEach((element) => {
		(element as HTMLElement).style.setProperty('--systemtag-color', `#${tag.color}`)
	})
}

/**
 *
 * @param tag
 * @param isMore
 */
function renderTag(tag: string, isMore = false): HTMLElement {
	const tagElement = document.createElement('li')
	tagElement.classList.add('files-list__system-tag')
	tagElement.setAttribute('data-systemtag-name', tag)
	tagElement.textContent = tag

	// Set the color if it exists
	const cachedTag = cache.find((t) => t.displayName === tag)
	if (cachedTag?.color) {
		// Make sure contrast is good and follow WCAG guidelines
		const mainBackgroundColor = getComputedStyle(document.body)
			.getPropertyValue('--color-main-background')
			.replace('#', '') || (isDarkModeEnabled() ? '000000' : 'ffffff')
		const primaryElement = elementColor(`#${cachedTag.color}`, `#${mainBackgroundColor}`)
		tagElement.style.setProperty('--systemtag-color', primaryElement)
		tagElement.setAttribute('data-systemtag-color', 'true')
	}

	if (isMore) {
		tagElement.classList.add('files-list__system-tag--more')
	}

	return tagElement
}

/**
 *
 * @param node
 */
async function renderInline(node: Node): Promise<HTMLElement> {
	// Ensure we have the system tags as an array
	const tags = getNodeSystemTags(node)

	const systemTagsElement = document.createElement('ul')
	systemTagsElement.classList.add('files-list__system-tags')
	systemTagsElement.setAttribute('aria-label', t('files', 'Assigned collaborative tags'))
	systemTagsElement.setAttribute('data-systemtags-fileid', node.fileid?.toString() || '')

	if (tags.length === 0) {
		return systemTagsElement
	}

	// Fetch the tags if the cache is empty
	if (cache.length === 0) {
		try {
			// Best would be to support attributes from webdav,
			// but currently the library does not support it
			cache.push(...await fetchTags())
		} catch (error) {
			logger.error('Failed to fetch tags', { error })
		}
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
