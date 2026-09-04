/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'
import type { SharingShare } from '../types/unifiedSharing.ts'

import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { generateOcsUrl } from '@nextcloud/router'
import { SOURCE_TYPE_NODE } from '../lib/unifiedSharingConstants.ts'
import logger from './logger.ts'

const PAGE_SIZE = 100

type Capabilities = {
	sharing?: {
		source_types?: { class: string }[]
	}
}

/**
 * Whether the node source type is registered on the server. The hardcoded class
 * string must match a capability entry, otherwise listing would silently return
 * nothing.
 */
function isNodeSourceTypeAvailable(): boolean {
	const capabilities = getCapabilities() as Capabilities
	return (capabilities.sharing?.source_types ?? []).some((type) => type.class === SOURCE_TYPE_NODE)
}

/**
 * Fetch every active share whose source is the given node, from the unified
 * sharing API. Results are paginated by the backend; this walks all pages.
 *
 * @param node The file or folder whose shares to list
 */
export async function getSharesForNode(node: Node): Promise<SharingShare[]> {
	if (!isNodeSourceTypeAvailable()) {
		logger.warn('Node source type is not advertised by the sharing capability; cannot list unified shares')
		return []
	}

	const url = generateOcsUrl('/apps/sharing/api/v1/shares')
	const shares: SharingShare[] = []
	let lastShareID: string | undefined

	// Walk pages until the backend returns a short (final) page.
	for (;;) {
		const response = await axios.get(url, {
			params: {
				filterSourceTypeClass: SOURCE_TYPE_NODE,
				filterSourceTypeValue: String(node.fileid),
				filterState: 'active',
				limit: PAGE_SIZE,
				...(lastShareID ? { lastShareID } : {}),
			},
		})
		const page: SharingShare[] = response.data.ocs.data
		shares.push(...page)
		if (page.length < PAGE_SIZE) {
			break
		}
		lastShareID = page[page.length - 1].id
	}

	return shares
}

/**
 * Delete a share by id.
 *
 * @param id The share id
 */
export async function deleteShare(id: string): Promise<void> {
	await axios.delete(generateOcsUrl('/apps/sharing/api/v1/share/{id}', { id }))
}

/**
 * Remove a single recipient from a share.
 *
 * @param id The share id
 * @param recipientClass The recipient type class
 * @param recipientValue The recipient value
 * @param instance The recipient's instance (federated recipients)
 */
export async function removeRecipient(id: string, recipientClass: string, recipientValue: string, instance?: string | null): Promise<void> {
	await axios.delete(generateOcsUrl('/apps/sharing/api/v1/share/{id}/recipient', { id }), {
		data: {
			class: recipientClass,
			value: recipientValue,
			instance: instance ?? null,
		},
	})
}
