/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { FileAction, registerFileAction } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'
import { t } from '@nextcloud/l10n'

import AutoRenewSvg from '@mdi/svg/svg/autorenew.svg?raw'

import { convertFile, convertFiles } from './convertUtils'

type ConversionsProvider = {
	from: string,
	to: string,
	displayName: string,
}

export const ACTION_CONVERT = 'convert'
export const registerConvertActions = () => {
	// Generate sub actions
	const convertProviders = getCapabilities()?.files?.file_conversions as ConversionsProvider[] ?? []
	const actions = convertProviders.map(({ to, from, displayName }) => {
		return new FileAction({
			id: `convert-${from}-${to}`,
			displayName: () => t('files', 'Save as {displayName}', { displayName }),
			iconSvgInline: () => generateIconSvg(to),
			enabled: (nodes: Node[]) => {
				// Check that all nodes have the same mime type
				return nodes.every(node => from === node.mime)
			},

			async exec(node: Node) {
				// If we're here, we know that the node has a fileid
				convertFile(node.fileid as number, to)

				// Silently terminate, we'll handle the UI in the background
				return null
			},

			async execBatch(nodes: Node[]) {
				const fileIds = nodes.map(node => node.fileid).filter(Boolean) as number[]
				convertFiles(fileIds, to)

				// Silently terminate, we'll handle the UI in the background
				return Array(nodes.length).fill(null)
			},

			parent: ACTION_CONVERT,
		})
	})

	// Register main action
	registerFileAction(new FileAction({
		id: ACTION_CONVERT,
		displayName: () => t('files', 'Save as …'),
		iconSvgInline: () => AutoRenewSvg,
		enabled: (nodes: Node[], view: View) => {
			return actions.some(action => action.enabled!(nodes, view))
		},
		async exec() {
			return null
		},
		order: 25,
	}))

	// Register sub actions
	actions.forEach(registerFileAction)
}

export const generateIconSvg = (mime: string) => {
	// Generate icon based on mime type
	const url = generateUrl('/core/mimeicon?mime=' + encodeURIComponent(mime))
	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg">
		<image href="${url}" height="32" width="32" />
	</svg>`
}
