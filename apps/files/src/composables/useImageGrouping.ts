/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'

// eslint-disable-next-line perfectionist/sort-named-imports
import { type Ref, computed } from 'vue'

export interface ImageGroupNode {
	_isImageGroup: true
	// Stable key for VirtualList recycled pool
	source: string
	images: INode[]
	expanded: boolean
}

export type GroupedNode = INode | ImageGroupNode | (INode & { _isGroupChild: true })

/**
 *
 * @param node
 */
export function isImageGroup(node: GroupedNode): node is ImageGroupNode {
	return '_isImageGroup' in node && node._isImageGroup === true
}

export interface ImageGroupingConfig {
	mimetypes: string[]
	timespanMinutes: number
}

/**
 *
 * @param node
 */
function getNodeTime(node: INode): number {
	const uploadTime = (node.attributes?.upload_time as number) ?? 0
	const crtime = (node.attributes?.crtime as number) ?? 0
	const mtime = node.mtime ? Math.floor(node.mtime / 1000) : 0
	return Math.max(uploadTime, crtime, mtime)
}

/**
 *
 * @param nodes
 * @param expandedGroups
 * @param config
 */
export function useImageGrouping(
	nodes: Ref<INode[]>,
	expandedGroups: Ref<Set<string>>,
	config: Ref<ImageGroupingConfig>,
) {
	return computed<GroupedNode[]>(() => {
		const result: GroupedNode[] = []
		let i = 0

		const { mimetypes, timespanMinutes } = config.value
		const timespan = timespanMinutes * 60

		if (mimetypes.length === 0) {
			return nodes.value
		}

		const isGroupable = (node: INode) => node.mime !== undefined && mimetypes.includes(node.mime)

		while (i < nodes.value.length) {
			const node = nodes.value[i]

			if (!isGroupable(node)) {
				result.push(node)
				i++
				continue
			}

			const groupStartTime = getNodeTime(node)

			// Look ahead: if any non-image falls within the timespan, don't group
			const isContaminated = groupStartTime && nodes.value.slice(i + 1).some((next) => {
				const nextTime = getNodeTime(next)
				if (!nextTime) {
					return false
				}
				if (Math.abs(nextTime - groupStartTime) > timespan) {
					return false
				}
				return !isGroupable(next)
			})

			if (isContaminated) {
				result.push(node)
				i++
				continue
			}

			// Start a new group from this image
			const images: INode[] = [node]
			i++

			while (i < nodes.value.length) {
				const next = nodes.value[i]

				if (!isGroupable(next)) {
					break
				}

				const nextTime = getNodeTime(next)
				if (!groupStartTime || !nextTime || Math.abs(nextTime - groupStartTime) > timespan) {
					break
				}

				images.push(next)
				i++
			}

			if (images.length === 1) {
				result.push(images[0])
				continue
			}

			const groupKey = `image-group-${images.map((n) => n.fileid).join('-')}`
			result.push({
				_isImageGroup: true,
				source: groupKey,
				images,
				expanded: expandedGroups.value.has(groupKey),
			})

			if (expandedGroups.value.has(groupKey)) {
				result.push(...images.map((img) => Object.assign(Object.create(Object.getPrototypeOf(img)), img, { _isGroupChild: true })))
			}
		}

		return result
	})
}
