/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import camelCase from 'camelcase'

import type { DAVResultResponseProps } from 'webdav'

import type { BaseTag, ServerTag, Tag, TagWithId } from './types.js'
import type { Node } from '@nextcloud/files'
import Vue from 'vue'

export const defaultBaseTag: BaseTag = {
	userVisible: true,
	userAssignable: true,
	canAssign: true,
}

export const parseTags = (tags: { props: DAVResultResponseProps }[]): TagWithId[] => {
	return tags.map(({ props }) => Object.fromEntries(
		Object.entries(props)
			.map(([key, value]) => [camelCase(key), camelCase(key) === 'displayName' ? String(value) : value]),
	)) as TagWithId[]
}

/**
 * Parse id from `Content-Location` header
 * @param url URL to parse
 */
export const parseIdFromLocation = (url: string): number => {
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

export const formatTag = (initialTag: Tag | ServerTag): ServerTag => {
	if ('name' in initialTag && !('displayName' in initialTag)) {
		return { ...initialTag }
	}

	const tag: Record<string, unknown> = { ...initialTag }
	tag.name = tag.displayName
	delete tag.displayName

	return tag as unknown as ServerTag
}

export const getNodeSystemTags = function(node: Node): string[] {
	const tags = node.attributes?.['system-tags']?.['system-tag'] as string|string[]|undefined

	if (tags === undefined) {
		return []
	}

	return [tags].flat()
}

export const setNodeSystemTags = function(node: Node, tags: string[]): void {
	Vue.set(node.attributes, 'system-tags', {
		'system-tag': tags,
	})
}
