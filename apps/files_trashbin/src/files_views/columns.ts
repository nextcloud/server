/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { Column, Node } from '@nextcloud/files'
import { formatRelativeTime, getCanonicalLocale, getLanguage, t } from '@nextcloud/l10n'
import { dirname } from '@nextcloud/paths'

import Vue from 'vue'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

export const originalLocation = new Column({
	id: 'files_trashbin--original-location',
	title: t('files_trashbin', 'Original location'),
	render(node) {
		const originalLocation = parseOriginalLocation(node)
		const span = document.createElement('span')
		span.title = originalLocation
		span.textContent = originalLocation
		return span
	},
	sort(nodeA, nodeB) {
		const locationA = parseOriginalLocation(nodeA)
		const locationB = parseOriginalLocation(nodeB)
		return locationA.localeCompare(locationB, [getLanguage(), getCanonicalLocale()], { numeric: true, usage: 'sort' })
	},
})

export const deletedBy = new Column({
	id: 'files_trashbin--deleted-by',
	title: t('files_trashbin', 'Deleted by'),
	render(node) {
		const { userId, displayName, label } = parseDeletedBy(node)
		if (label) {
			const span = document.createElement('span')
			span.textContent = label
			return span
		}

		const UserBubble = Vue.extend(NcUserBubble)
		const propsData = {
			size: 32,
			user: userId ?? undefined,
			displayName: displayName ?? userId,
		}
		const userBubble = new UserBubble({ propsData }).$mount().$el
		return userBubble as HTMLElement
	},
	sort(nodeA, nodeB) {
		const deletedByA = parseDeletedBy(nodeA)
		const deletedbyALabel = deletedByA.label ?? deletedByA.displayName ?? deletedByA.userId
		const deletedByB = parseDeletedBy(nodeB)
		const deletedByBLabel = deletedByB.label ?? deletedByB.displayName ?? deletedByB.userId
		// label is set if uid and display name are unset - if label is unset at least uid or display name is set.
		return deletedbyALabel!.localeCompare(deletedByBLabel!, [getLanguage(), getCanonicalLocale()], { numeric: true, usage: 'sort' })
	},
})

export const deleted = new Column({
	id: 'files_trashbin--deleted',
	title: t('files_trashbin', 'Deleted'),

	render(node) {
		const deletionTime = node.attributes?.['trashbin-deletion-time'] || ((node?.mtime?.getTime() ?? 0) / 1000)
		const span = document.createElement('span')
		if (deletionTime) {
			const formatter = Intl.DateTimeFormat([getCanonicalLocale()], { dateStyle: 'long', timeStyle: 'short' })
			const timestamp = new Date(deletionTime * 1000)

			span.title = formatter.format(timestamp)
			span.textContent = formatRelativeTime(timestamp, { ignoreSeconds: t('files', 'few seconds ago') })
			return span
		}

		// Unknown deletion time
		span.textContent = t('files_trashbin', 'A long time ago')
		return span
	},

	sort(nodeA, nodeB) {
		// deletion time is a unix timestamp while mtime is a JS Date -> we need to align the numbers (seconds vs milliseconds)
		const deletionTimeA = nodeA.attributes?.['trashbin-deletion-time'] || ((nodeA?.mtime?.getTime() ?? 0) / 1000)
		const deletionTimeB = nodeB.attributes?.['trashbin-deletion-time'] || ((nodeB?.mtime?.getTime() ?? 0) / 1000)
		return deletionTimeB - deletionTimeA
	},
})

/**
 * Get the original file location of a trashbin file.
 *
 * @param node The node to parse
 */
function parseOriginalLocation(node: Node): string {
	const path = stringOrNull(node.attributes?.['trashbin-original-location'])
	if (!path) {
		return t('files_trashbin', 'Unknown')
	}

	const dir = dirname(path)
	if (dir === path) { // Node is in root folder
		return t('files_trashbin', 'All files')
	}

	return dir.replace(/^\//, '')
}

/**
 * Parse a trashbin file to get information about the user that deleted the file.
 *
 * @param node The node to parse
 */
function parseDeletedBy(node: Node) {
	const userId = stringOrNull(node.attributes?.['trashbin-deleted-by-id'])
	const displayName = stringOrNull(node.attributes?.['trashbin-deleted-by-display-name'])

	let label: string|undefined
	const currentUserId = getCurrentUser()?.uid
	if (userId === currentUserId) {
		label = t('files_trashbin', 'You')
	}
	if (!userId && !displayName) {
		label = t('files_trashbin', 'Unknown')
	}

	return {
		userId,
		displayName,
		label,
	}
}

/**
 * If the attribute is given it will be stringified and returned - otherwise null is returned.
 *
 * @param attribute The attribute to check
 */
function stringOrNull(attribute: unknown): string | null {
	if (attribute) {
		return String(attribute)
	}
	return null
}
