/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { DAVResultResponseProps } from 'webdav'
import type { BaseTag, ServerTag, Tag, TagWithId } from './types.ts'

import { emit } from '@nextcloud/event-bus'

export const defaultBaseTag: BaseTag = {
	userVisible: true,
	userAssignable: true,
	canAssign: true,
}

const propertyMappings = Object.freeze({
	'display-name': 'displayName',
	'user-visible': 'userVisible',
	'user-assignable': 'userAssignable',
	'can-assign': 'canAssign',
})

/**
 * Parse tags from WebDAV response
 *
 * @param tags - Array of tags from WebDAV response
 */
export function parseTags(tags: { props: DAVResultResponseProps }[]): TagWithId[] {
	return tags.map(({ props }) => Object.fromEntries(Object.entries(props)
		.map(([key, value]) => {
			key = propertyMappings[key] ?? key
			value = key === 'displayName' ? String(value) : value
			return [key, value]
		})) as unknown as TagWithId)
}

/**
 * Parse id from `Content-Location` header
 *
 * @param url URL to parse
 */
export function parseIdFromLocation(url: string): number {
	const queryPos = url.indexOf('?')
	if (queryPos > 0) {
		url = url.substring(0, queryPos)
	}

	const parts = url.split('/')
	let result
	do {
		result = parts[parts.length - 1]
		parts.pop()
		// note: first result can be empty when there is a trailing slash,
		// so we take the part before that
	} while (!result && parts.length > 0)

	return Number(result)
}

/**
 * Format a tag for WebDAV operations
 *
 * @param initialTag - Tag to format
 */
export function formatTag(initialTag: Tag | ServerTag): ServerTag {
	if ('name' in initialTag && !('displayName' in initialTag)) {
		return { ...initialTag }
	}

	const tag: Record<string, unknown> = { ...initialTag }
	tag.name = tag.displayName
	delete tag.displayName

	return tag as unknown as ServerTag
}

/**
 * Get system tags from a node
 *
 * @param node - The node to get tags from
 */
export function getNodeSystemTags(node: INode): string[] {
	const attribute = node.attributes?.['system-tags']?.['system-tag']
	if (attribute === undefined) {
		return []
	}

	// if there is only one tag it is a single string or prop object
	// if there are multiple then its an array - so we flatten it to be always an array of string or prop objects
	return [attribute]
		.flat()
		.map((tag: string | { text: string }) => (
			typeof tag === 'string'
				// its a plain text prop (the tag name) without prop attributes
				? tag
				// its a prop object with attributes, the tag name is in the 'text' attribute
				: tag.text
		))
}

/**
 * Set system tags on a node
 *
 * @param node - The node to set tags on
 * @param tags - The tags to set
 */
export function setNodeSystemTags(node: INode, tags: string[]): void {
	node.attributes['system-tags'] = {
		'system-tag': tags,
	}
	emit('files:node:updated', node)
}
